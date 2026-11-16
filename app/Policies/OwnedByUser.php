<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait OwnedByUser
{
    public function view(User $user, Model $model): bool
    {
        return (int) $model->getAttribute('user_id') === $user->id;
    }

    public function update(User $user, Model $model): bool
    {
        return $this->view($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->view($user, $model);
    }
}
