<?php

namespace App\Http\Controllers\AppApi;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeamController extends Controller
{
    public function my(Request $request): JsonResponse
    {
        $user = $request->user();

        $teams = Team::query()
            ->with(['lead', 'members'])
            ->where('lead_user_id', $user->id)
            ->orWhereHas('members', fn ($query) => $query->where('users.id', $user->id))
            ->get()
            ->unique('id')
            ->values();

        return response()->json([
            'success' => true,
            'rows' => $teams->map(fn (Team $team) => $this->transformTeam($team))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ])->validate();

        $team = Team::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'lead_user_id' => $request->user()->id,
        ]);
        $team->members()->syncWithoutDetaching([$request->user()->id]);

        return response()->json([
            'success' => true,
            'message' => 'Team created successfully.',
            'row' => $this->transformTeam($team->fresh(['lead', 'members'])),
        ], 201);
    }

    public function link(Request $request): JsonResponse
    {
        $data = Validator::make($request->all(), [
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ])->validate();

        $team = Team::query()->with(['lead', 'members'])->findOrFail($data['team_id']);
        abort_if($team->lead_user_id !== $request->user()->id && $request->user()->role !== 'admin', 403, 'Forbidden');

        $team->members()->syncWithoutDetaching([$data['user_id']]);

        return response()->json([
            'success' => true,
            'message' => 'Member linked successfully.',
            'row' => $this->transformTeam($team->fresh(['lead', 'members'])),
        ]);
    }

    public function memberOptions(Request $request): JsonResponse
    {
        $teamId = (int) $request->query('team_id');
        $team = Team::query()->with('members')->findOrFail($teamId);

        abort_if($team->lead_user_id !== $request->user()->id && $request->user()->role !== 'admin', 403, 'Forbidden');

        $memberIds = $team->members->pluck('id')->all();

        $options = User::query()
            ->when(! empty($memberIds), fn ($query) => $query->whereNotIn('id', $memberIds))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->display_name,
                'email' => $user->email,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'options' => $options,
        ]);
    }

    private function transformTeam(Team $team): array
    {
        return [
            'id' => $team->id,
            'name' => $team->name,
            'description' => $team->description,
            'lead_name' => $team->lead?->display_name,
            'member_count' => $team->members->count(),
            'members' => $team->members->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->display_name,
                'email' => $user->email,
            ])->values(),
        ];
    }
}
