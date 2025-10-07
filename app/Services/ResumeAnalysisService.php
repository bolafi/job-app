<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;
use PhpParser\Node\Stmt\TryCatch;
use Spatie\PdfToText\Pdf;

class ResumeAnalysisService
{
    public function extractResumeInformation(string $fileUrl)
    {
        try {
            // Extract raw text from the resume PDF file (read pdf file, and get the text content)
            $rawText = $this->extractTextFromPdf($fileUrl);

            Log::debug('Successfully extracted text from resume PDF' . strlen($rawText) . 'characters');

            // Use OpenAI API to organize the text into a structured format
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a precise resume parser. Extract information exactly as it appears in the resume without adding any interpretation or additional information. The output should be in JSON format.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Parse the following resume content and extract the information as a JSON Object with the exact keys: 'summary', 'skills', 'experience', 'education'. The resume content is :{$rawText}. Return an empty string for any key that if not found."
                    ]
                ],
                'response_format' => [
                    'type' => 'json_object'
                ],
                'temperature' => 0.1, // set the randomness of the ai response

            ]);

            $result = $response->choices[0]->message->content;
            Log::debug('OpenAI response: ' . $result);

            // Turn to associative array
            $parsedResult = json_decode($result, true);
            Log::debug('Parsed result: ' . print_r($parsedResult, true));

            if (json_last_error() !== JSON_ERROR_NONE) {
                log::error('Failed to parse JSON from OpenAI response: ' . json_last_error_msg());
                throw new \Exception('Failed to parse OpenAI response');
            }

            // Validate the parsed result
            $requiredKeys = ['summary', 'skills', 'experience', 'education'];
            $missingKeys = array_diff($requiredKeys, array_keys($parsedResult));

            if (count($missingKeys) > 0) {
                log::error('Missing required keys in parsed result: ' . implode(', ', $missingKeys));
                throw new \Exception('Missing required keys in parsed result');
            }

            // flatten the nested arrays
            $flattenedArray = $this->flattenArray($parsedResult);

            Log::debug('Flattened result: ' . print_r($flattenedArray, true));

            // Return the JSON object
            return [
                'summary' => $flattenedArray['summary'] ?? '',
                'skills' => $flattenedArray['skills'] ?? '',
                'experience' => $flattenedArray['experience'] ?? '',
                'education' => $flattenedArray['education'] ?? ''
            ];
        } catch (\Exception $e) {
            Log::error('Error occurred while extracting resume information: ' . $e->getMessage());

            return [
                'summary' => '',
                'skills' => '',
                'experience' => '',
                'education' => ''
            ];
        }
    }

    public function analyzeResume($jobVacancy, $resumeData)
    {
        try {
            $jobDatils = json_encode([
                'job_title' => $jobVacancy->title,
                'job_description' => $jobVacancy->description,
                'job_location' => $jobVacancy->location,
                'job_type' => $jobVacancy->type,
                'job_salary' => $jobVacancy->salary,
            ]);

            $resumeDetails = json_encode($resumeData);

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are an expert HR professional and job recruiter.
                        You are given a job vacancy and a resume.
                        Your task is to analyze the resume and determine if the candidate is a good fit for the job.
                        The output should be a JSON format.
                        Provide a score from 0 to 100 for the candidate suitability for the job, and a detailed feedback.
                        Response should only be Json that has the following keys: 'aiGeneratedScore', 'aiGeneratedFeedback'.
                        Aigenerated feedback should be detailed and specific to the job and candidate's resume."
                    ],
                    [
                        'role' => 'user',
                        'content' => "Please evaluate this job application. Job Details: {$jobDatils}. Resume details: {$resumeDetails}."
                    ]
                ],
                'response_format' => [
                    'type' => 'json_object'
                ],
                'temperature' => 0.1, // set the randomness of the ai response
            ]);
            $result = $response->choices[0]->message->content;
            Log::debug('OpenAI Evaluation: ' . $result);

            $parsedResult = json_decode($result, true);
            Log::debug('Parsed Evaluation: ' . print_r($parsedResult, true));

            if (json_last_error() !== JSON_ERROR_NONE) {
                log::error('Failed to parse JSON from OpenAI evaluation response: ' . json_last_error_msg());
                throw new \Exception('Failed to parse OpenAI evaluation response');
            }

            if (!isset($parsedResult['aiGeneratedScore']) || !isset($parsedResult['aiGeneratedFeedback'])) {
                log::error('Missing required keys in evaluation result');
                throw new \Exception('Missing required keys in evaluation result');
            }

            return $parsedResult;
        } catch (\Throwable $th) {
            Log::error('Error occurred while analyzing resume: ' . $th->getMessage());
            return [
                'aiGeneratedScore' => 0,
                'aiGeneratedFeedback' => 'An error occurred during resume analysis. Please try again later.'
            ];
        }
    }

    private function extractTextFromPdf(string $fileUrl): string
    {
        /* Reading the file from the cloud to disk storage in temp file */
        $tempFile = tempnam(sys_get_temp_dir(), 'resume');

        $filePath = parse_url($fileUrl, PHP_URL_PATH);
        if (!$filePath) {
            throw new \Exception('Invalid file URL');
        }

        $filename = basename($filePath);

        $storagePath = "resumes/{$filename}";

        if (!Storage::exists($storagePath)) {
            throw new \Exception('File does not exist in storage');
        }

        $pdfContent = Storage::disk('cloud')->get($storagePath);
        if (!$pdfContent) {
            throw new \Exception('Failed to read file');
        }

        file_put_contents($tempFile, $pdfContent);

        /* Check if pdf-to-text is installed */

        $pdfToTextPath = ['/usr/bin/pdftotext', '/usr/local/bin/pdftotext', '/opt/homebrew/bin/pdftotext'];
        $pdfToTextAvailable = false;
        foreach ($pdfToTextPath as $path) {
            if (file_exists($path)) {
                $pdfToTextAvailable = true;
                break;
            }
        }
        if (!$pdfToTextAvailable) {
            throw new \Exception('pdftotext is not installed');
        }

        /* Extract text from pdf file */
        $instance = new Pdf();
        $instance->setPdf($tempFile);
        $text = $instance->text();

        // Clean up the temporary file
        unlink($tempFile);

        return $text;
    }

    private function flattenArray(array $arr): array
    {
        $output = [];
        foreach ($arr as $key => $value) {
            $output[$key] = $this->recursiveFlatten($value);
        }
        return $output;
    }

    private function recursiveFlatten($value): string
    {
        if (is_array($value)) {
            $flattened = [];
            foreach ($value as $item) {
                $flattened[] = $this->recursiveFlatten($item);
            }
            return implode(", ", array_filter($flattened));
        } else {
            return (string) $value;
        }
    }
}