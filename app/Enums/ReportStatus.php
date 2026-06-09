<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Draft = 'draft';
    case Signed = 'signed';
    case Closed = 'closed';
}
