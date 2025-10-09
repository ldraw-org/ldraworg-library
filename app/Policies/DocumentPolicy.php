<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Models\Document\Document;

class DocumentPolicy
{
    public function view(?User $user, Document $document)
    {
        if (!$document->published) {
            return !is_null($user) && $user->can(Permission::DocumentViewUnpublished);
        } elseif ($document->restricted) {
            return !is_null($user) && $user->can(Permission::DocumentViewRestricted); 
        }
        return true;  
    }

    public function manage(User $user)
    {
        return $user->can(Permission::DocumentManage);
    }
}
