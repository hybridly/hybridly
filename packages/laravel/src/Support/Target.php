<?php

namespace Hybridly\Support;

/**
 * Redirection target.
 */
enum Target: string
{
    /**
     * Opens in the current tab.
     */
    case CURRENT = 'current';

    /**
     * Opens in a new tab.
     */
    case NEW_TAB = 'new-tab';
}
