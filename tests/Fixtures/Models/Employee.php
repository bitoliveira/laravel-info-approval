<?php

namespace bitoliveira\Approval\Tests\Fixtures\Models;

use bitoliveira\Approval\Traits\HasApprovals;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasApprovals;

    protected $guarded = [];
}
