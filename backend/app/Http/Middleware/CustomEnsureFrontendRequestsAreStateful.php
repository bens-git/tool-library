<?php

namespace App\Http\Middleware;

use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful as SanctumEnsureFrontendRequestsAreStateful;

class CustomEnsureFrontendRequestsAreStateful extends SanctumEnsureFrontendRequestsAreStateful
{
    protected function configureSecureCookieSessions()
    {
        config(['session.same_site' => 'none']);
        config(['session.secure' => true]);  // Ensure secure is set as well
        echo "test1";

        parent::configureSecureCookieSessions();
    }
    
}
