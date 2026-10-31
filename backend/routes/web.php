<?php

use Illuminate\Support\Facades\Route;
// STSAMS admissions web portal: isolated from the existing API routes.
require __DIR__.'/stsams_portal.php';

// Saint Titus institutional website; admissions routes remain unchanged.
require __DIR__.'/stsams_institution.php';
