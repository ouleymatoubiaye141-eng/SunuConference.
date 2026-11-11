<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Services\SubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionFileController extends Controller
{
    public function __construct(private readonly SubmissionService $submissionService) {}

    public function download(Request $request, Submission $submission, string $type): StreamedResponse
    {
        $file = $this->submissionService->resolveFileForDownload($request->user(), $submission, $type);

        return Storage::disk('local')->download($file['path'], $file['filename']);
    }
}
