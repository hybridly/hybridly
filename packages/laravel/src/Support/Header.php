<?php

namespace Hybridly\Support;

final class Header
{
    public const HYBRID_REQUEST = 'x-hybrid';
    public const DIALOG_KEY = 'x-hybrid-dialog-key';
    public const DIALOG_REDIRECT = 'x-hybrid-dialog-redirect';
    public const EXTERNAL = 'x-hybrid-external';
    public const EXTERNAL_TARGET = 'x-hybrid-external-target';
    public const PARTIAL_COMPONENT = 'x-hybrid-partial-component';
    public const PARTIAL_ONLY = 'x-hybrid-only-data';
    public const PARTIAL_EXCEPT = 'x-hybrid-except-data';
    public const ERROR_BAG = 'x-hybrid-error-bag';
    public const VERSION = 'x-hybrid-version';
}
