@local @local_wsmanager
Feature: Schema dashboard
  In order to manage web service schemas
  As an administrator
  I need to view and manage schemas from the dashboard

  Background:
    Given I log in as "admin"
    And I navigate to "Server > Web Service Manager > Dashboard" in site administration

  @javascript
  Scenario: View empty dashboard
    Then I should see "Web Service Manager"
    And I should see "Dashboard"
    And I should see "Import Schemas"
    And I should see "No schemas have been defined yet"

  @javascript
  Scenario: Navigate to the import page
    When I click on "Import Schemas" "link"
    Then I should see "Import Schemas"
    And I should see "Import File"

  @javascript
  Scenario: Navigate to the documentation page
    When I click on "Import Schemas" "link"
    And I click on "View Documentation" "link"
    Then I should see "YAML Schema Reference"
    And I should see "Schema Structure"
    And I should see "Back"
