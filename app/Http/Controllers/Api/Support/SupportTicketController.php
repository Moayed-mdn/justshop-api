<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SupportTicketController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['message' => 'Support tickets list']);
    }

    public function show(int $ticket): JsonResponse
    {
        return response()->json(['message' => 'Support ticket details', 'ticket_id' => $ticket]);
    }

    public function assign(int $ticket): JsonResponse
    {
        return response()->json(['message' => 'Ticket assigned']);
    }

    public function resolve(int $ticket): JsonResponse
    {
        return response()->json(['message' => 'Ticket resolved']);
    }

    public function addNote(int $ticket): JsonResponse
    {
        return response()->json(['message' => 'Note added']);
    }
}
