# Account Registration — Living Spec

> [DRAFT] Adopted from the code's surface and the project's conventions. Review before trusting.

## Purpose

Lets a person create an account and prove they own its email address. Without it nobody can sign in, and an account could be opened with an email the person does not control.

## Requirements

### A person registers with name, email, password and SpaceTraders account token
<!-- touches: src/Controller/Auth/RegisterController.php, src/Security/UserRegistrationService.php, src/Security/Dto/RegisterUserDto.php, src/Security/Dto/RegisterResponseData.php, src/Entity/User.php -->
<!-- adopted: src/Controller/Auth/RegisterController.php -->

`POST /auth/register` creates an unverified account and answers 201 with its id, name and email, never the password. The SpaceTraders account token is stored encrypted. The password needs at least 8 characters and the email must be valid.

#### Scenario: a valid registration
- **WHEN** someone posts a name, a valid email, an 8-character password and an account token
- **THEN** they get 201 with id, name and email, the body carries no password, and the account is unverified

#### Scenario: a password that is too short
- **WHEN** the password has fewer than 8 characters
- **THEN** they get 422 `validation_failed` with the problem listed under `password`

### An email can be registered only once
<!-- touches: src/Security/UserRegistrationService.php, src/Security/Exception/EmailAlreadyRegisteredException.php, src/EventListener/ErrorMapper/EmailAlreadyRegisteredExceptionErrorMapper.php, src/Repository/UserRepository.php -->
<!-- adopted: src/Security/UserRegistrationService.php -->

Registering an email that already has an account fails and creates nothing.

#### Scenario: the same email twice
- **WHEN** someone registers an email that already has an account
- **THEN** they get 409 `email_already_registered`

### Registering sends a confirmation link that points at the front
<!-- touches: src/Security/Email/**, src/Controller/Auth/RegisterController.php -->
<!-- adopted: src/Security/Email/RegistrationConfirmationMailer.php -->

After registering, the person receives an email from `MAILER_FROM` with a link to `{FRONT_URL}/confirm-email`. The link carries the signed query unchanged, so the front can forward it as-is to the back.

#### Scenario: the link is followed
- **WHEN** the front forwards the link's query to `POST /auth/confirm-email`
- **THEN** the account becomes verified and the person can sign in

### Confirming an email needs an intact, unexpired signed link
<!-- touches: src/Controller/Auth/ConfirmEmailController.php, src/Security/EmailConfirmationService.php, src/Security/Exception/InvalidEmailConfirmationException.php, src/EventListener/ErrorMapper/InvalidEmailConfirmationExceptionErrorMapper.php, src/Security/Dto/ConfirmEmailResponseData.php -->
<!-- adopted: src/Security/EmailConfirmationService.php -->

`POST /auth/confirm-email` verifies the account named by the signed query. Every failure gets the same 400 `invalid_email_confirmation` with no per-field violations: a missing query, a tampered signature, an expired link, an unknown user id. The answer never tells the caller which parameter was wrong or which user ids exist.

#### Scenario: a tampered link
- **WHEN** any parameter of the signed query is altered
- **THEN** they get 400 `invalid_email_confirmation` and the account stays unverified

#### Scenario: an unknown user id
- **WHEN** the query names a user id that does not exist
- **THEN** the response is exactly the one a tampered signature gets

#### Scenario: no query at all
- **WHEN** the request carries no query
- **THEN** they get 400 `invalid_email_confirmation`, not `validation_failed`

### Resending the confirmation never reveals whether an email is registered
<!-- touches: src/Controller/Auth/ResendConfirmationController.php, src/Security/Dto/ResendConfirmationDto.php, src/Security/Dto/EmptyResponseData.php -->
<!-- adopted: src/Controller/Auth/ResendConfirmationController.php -->

`POST /auth/resend-confirmation` always answers 202 with empty data. A new link goes out only when the email belongs to an unverified account.

#### Scenario: an unverified account
- **WHEN** someone asks to resend for an unverified account's email
- **THEN** a confirmation email is sent and they get 202

#### Scenario: an unknown or already verified email
- **WHEN** the email has no account, or its account is already verified
- **THEN** no email is sent and they still get 202

## Uncovered

- The stored SpaceTraders account token is not used by any flow yet. The developer confirmed it will be used when the first call to the SpaceTraders API that needs it is implemented.
