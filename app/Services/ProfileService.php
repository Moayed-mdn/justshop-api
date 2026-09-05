<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    /**
     * Update user profile information.
     */
    public function updateProfile(User $user, array $data): User
    {
        if ($user->email !== $data['email']) {
            $data['email_verified_at'] = null;
        }

        $user->update($data);

        return $user->fresh();
    }

    /**
     * Update user password.
     */
    public function changePassword(User $user, string $password): void
    {
        $user->update([
            'password' => Hash::make($password),
        ]);
    }

    /**
     * Update user avatar.
     */
    public function updateAvatar(User $user, UploadedFile $file): string
    {
        if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
            Storage::delete($user->avatar);
        }

        $path = $file->store('avatars');

        $user->update(['avatar' => $path]);

        return Storage::url($path);
    }

    /**
     * Delete user account.
     */
    public function deleteAccount(User $user): void
    {
        $user->tokens()->delete();

        // Release unique constraints so the user can re-register.
        $user->email = 'deleted_' . $user->id . '_' . time() . '@deleted.local';
        $user->google_id = null;
        $user->save();

        $user->delete();
    }
}
