<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function view(User $user, Tag $tag): bool
    {
        return $user->id === $tag->user_id;
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->id === $tag->user_id && !$tag->is_default;
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->id === $tag->user_id && !$tag->is_default;
    }
}
