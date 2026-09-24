@admin_action_log
Feature: Logging the administrators actions
    In order to know who did what in the administration
    As a Store Owner
    I want every administrator action to be saved and browsable

    Background:
        Given the store operates on a single channel in "United States"

    @ui
    Scenario: Consultations and modifications are logged
        Given I am logged in as an administrator with no context role
        When I open my account page
        And I change my first name to "Rick" and save
        Then a "read" administrator action on the "sylius_admin_admin_user_update" route should have been logged for "sylius@example.com"
        And a "write" administrator action on the "sylius_admin_admin_user_update" route should have been logged for "sylius@example.com"

    @ui
    Scenario: Administrators with the audit role can browse the logged actions
        Given I am logged in as an administrator with the "ROLE_ADMIN_AUDIT_READ" roles
        When I open my account page
        And I browse the administrator actions
        Then I should see the "sylius_admin_admin_user_update" route in the administrator actions list
        When I show the first administrator action on the "sylius_admin_admin_user_update" route
        Then I should see the administrator action details of "sylius@example.com"
        And no administrator action on the "akawaka_sylius_security_admin_admin_action_log_index" route should have been logged
        And no administrator action on the "akawaka_sylius_security_admin_admin_action_log_show" route should have been logged
        And I should see only the "Configuration" sections in the main menu

    @ui
    Scenario: Browsing the logged actions page by page
        Given there are 30 administrator action logs
        And I am logged in as an administrator with the "ROLE_ADMIN_AUDIT_READ" roles
        When I browse the administrator actions
        Then I should see the pagination of the administrator actions
        And I should see 20 administrator actions in the list
        When I go to the page 2 of the administrator actions
        Then I should see 10 administrator actions in the list

    @ui
    Scenario: Administrators without the audit role cannot browse the logged actions
        Given I am logged in as an administrator with no context role
        When I try to open the "akawaka_sylius_security_admin_admin_action_log_index" admin page
        Then I should be told that "You are not allowed to access the Audit section."

    @ui
    Scenario: Failed and successful logins are logged
        Given there is an administrator "sylius@example.com" with no context role
        When I try to log in to the administration as "sylius@example.com" with the password "wrong"
        Then a "login_failure" administrator action on the "sylius_admin_login_check" route should have been logged for "sylius@example.com"
        When I try to log in to the administration as "sylius@example.com" with the password "sylius"
        Then a "login" administrator action on the "sylius_admin_login_check" route should have been logged for "sylius@example.com"
        And a "read" administrator action on the "sylius_admin_dashboard" route should have been logged for "sylius@example.com"

    @ui
    Scenario: Purging the old logs
        Given there is an administrator action log on the "sylius_admin_product_index" route dated 100 days ago
        And there is an administrator action log on the "sylius_admin_order_index" route dated 10 days ago
        When I purge the administrator action logs older than 30 days
        Then there should be only an administrator action log on the "sylius_admin_order_index" route
