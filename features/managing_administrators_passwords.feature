@admin_context_access
Feature: Managing administrators passwords
    In order to keep administrators accounts secure
    As an Administrator
    I want to change only my own password and send a password reset email to other administrators

    Background:
        Given the store operates on a single channel in "United States"

    @ui
    Scenario: Changing my own password
        Given I am logged in as an administrator with no context role
        When I open my account page
        Then I should be able to change the password

    @ui
    Scenario: Requesting a password reset for another administrator
        Given I am logged in as an administrator with the "ROLE_ADMIN_ADMINISTRATION_WRITE" roles
        And there is an administrator "john@example.com" with no context role
        When I try to open the account page of the administrator "john@example.com"
        Then I should not be able to change the password but only request a reset
        When I request a password reset for this administrator
        Then I should be notified that the password reset email has been sent
        And an email with reset token should be sent to "john@example.com"

    @ui
    Scenario: Super administrators cannot change the password of another administrator either
        Given I am logged in as an administrator with the "ROLE_SUPER_ADMIN" roles
        And there is an administrator "john@example.com" with no context role
        When I try to open the account page of the administrator "john@example.com"
        Then I should not be able to change the password but only request a reset
