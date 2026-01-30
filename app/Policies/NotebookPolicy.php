<?php

namespace App\Policies;

use App\Models\Notebook;
use App\Models\User;

class NotebookPolicy
{
    /**
     * Determine if the user can view the notebook.
     */
    public function view(User $user, Notebook $notebook): bool
    {
        return $user->id === $notebook->user_id;
    }

    /**
     * Determine if the user can update the notebook.
     */
    public function update(User $user, Notebook $notebook): bool
    {
        return $user->id === $notebook->user_id;
    }

    /**
     * Determine if the user can delete the notebook.
     */
    public function delete(User $user, Notebook $notebook): bool
    {
        return $user->id === $notebook->user_id;
    }
}
