<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \Spatie\Permission\Models\Permission as PermissionsModel;

class Permission extends PermissionsModel
{
    use HasFactory;
}
