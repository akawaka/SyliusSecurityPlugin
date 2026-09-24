@admin_context_access
Feature: Restricting the administration contexts by roles
    In order to limit what each administrator can do
    As a Store Owner
    I want the administration contexts to be accessible only with the matching roles

    Background:
        Given the store operates on a single channel in "United States"

    @ui
    Scenario: An administrator without context role only reaches the dashboard
        Given I am logged in as an administrator with no context role
        When I try to open the "sylius_admin_dashboard" admin page
        Then I should be granted access
        When I try to open the "sylius_admin_product_index" admin page
        Then I should be denied access
        And I should see only the "" sections in the main menu

    @ui
    Scenario: An administrator with a read role can browse but not modify a context
        Given I am logged in as an administrator with the "ROLE_ADMIN_CATALOG_READ" roles
        When I try to open the "sylius_admin_product_index" admin page
        Then I should be granted access
        When I try to open the "sylius_admin_product_create_simple" admin page
        Then I should be told that "You can only view the Catalog section, modifying it is not allowed."
        When I try to open the "sylius_admin_order_index" admin page
        Then I should be told that "You are not allowed to access the Sales section."
        And I should see only the "Catalog" sections in the main menu

    @ui
    Scenario: An administrator with a write role can modify a context
        Given I am logged in as an administrator with the "ROLE_ADMIN_CATALOG_WRITE, ROLE_ADMIN_SALES_READ" roles
        When I try to open the "sylius_admin_product_create_simple" admin page
        Then I should be granted access
        And I should see only the "Catalog, Sales" sections in the main menu

    @ui
    Scenario: A super administrator can access every context
        Given I am logged in as an administrator with the "ROLE_SUPER_ADMIN" roles
        When I try to open the "sylius_admin_channel_index" admin page
        Then I should be granted access
        And I should see only the "Catalog, Sales, Customer, Marketing, Configuration" sections in the main menu

    @ui
    Scenario: Assigning context roles to an administrator
        Given I am logged in as an administrator with the "ROLE_ADMIN_ADMINISTRATION_WRITE" roles
        And there is an administrator "john@example.com" with the "ROLE_API_ACCESS" roles
        When I assign the "ROLE_ADMIN_CATALOG_WRITE, ROLE_ADMIN_SALES_READ" roles to the administrator "john@example.com"
        Then the administrator "john@example.com" should have the "ROLE_ADMIN_CATALOG_WRITE, ROLE_ADMIN_SALES_READ, ROLE_API_ACCESS" roles

    @ui
    Scenario: An administrator without context role can still edit their own account
        Given I am logged in as an administrator with no context role
        When I open my account page
        Then I should be granted access
        And I should not be able to change roles
        When I change my first name to "John" and save
        Then I should be back on my account page
        And my first name should be "John"

    @ui
    Scenario: An administrator without context role cannot edit another administrator
        Given I am logged in as an administrator with no context role
        And there is an administrator "john@example.com" with no context role
        When I try to open the account page of the administrator "john@example.com"
        Then I should be denied access
