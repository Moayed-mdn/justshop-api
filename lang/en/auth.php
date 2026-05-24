<?php

return [
    'invalid_credentials' => 'Invalid credentials.',
    'unauthorized' => 'Unauthorized.',
    'email_verification_failed' => 'Email verification failed.',
    'password_reset_failed' => 'Password reset failed.',
    'social_auth_failed' => 'Social authentication failed.',
    'too_many_requests' => 'Too many requests. Please try again later.',
    'too_many_login_attempts' => 'Too many login attempts. Please try again in a minute.',
    'account_disabled' => 'Your account has been disabled. Please contact support.',
    'session_expired' => 'Your session has expired.',
    'onboarding_incomplete' => 'Merchant onboarding is incomplete.',
    'identity_domain_access_denied' => 'This route is not available for the current identity context.',
    'customer_account_customer_only' => 'This storefront account route is reserved for customer identities.',
    'customer_register_success' => 'Customer account registered successfully.',
    'customer_login_successful' => 'Customer login successful.',
    'customer_logout_successful' => 'Customer logout successful.',
    'customer_me_successful' => 'Customer identity loaded successfully.',
    'customer_bootstrap_successful' => 'Customer bootstrap loaded successfully.',

    // Registration & verification
    'register_success' => 'Registration successful. Please verify your email.',
    'login_successful' => 'Login successful.',
    'logout_successful' => 'Logged out successfully.',
    'already_verified' => 'Email is already verified.',
    'email_verified' => 'Email verified successfully.',
    'email_already_verified' => 'Email is already verified.',
    'verification_email_sent' => 'Verification email sent.',
    'verification_link_invalid' => 'The verification link is invalid or has expired.',
    'too_many_attempts' => 'Too many attempts. Please wait :seconds seconds.',

    // Bootstrap
    'bootstrap_successful' => 'Bootstrap loaded successfully.',
    'active_store_updated' => 'Active store updated successfully.',

    // Account deletion
    'account_deletion_password_required' => 'Please confirm your password to delete your account.',
    'account_deletion_password_incorrect' => 'The provided password is incorrect.',

    // Session management
    'sessions_retrieved' => 'Active sessions retrieved successfully.',
    'session_revoked' => 'Session revoked successfully.',
    'all_other_sessions_revoked' => 'All other sessions have been revoked.',
    'password_required_for_session_revocation' => 'Please confirm your password to revoke all other sessions.',
    'password_incorrect' => 'The provided password is incorrect.',
    'token_valid' => 'The reset token is valid.',
    'token_invalid' => 'The reset token is invalid or has expired.',
];
