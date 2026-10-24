<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /** A phone number written any common way, with 7 to 15 digits (validation regex). */
    public const string PHONE_REGEX = 'regex:/^(?:\D*\d){7,15}\D*$/';
}
