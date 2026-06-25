@local @local_servicemanager
Feature: Schema editing
  In order to update web service configurations
  As an administrator
  I need to edit existing schemas

  Background:
    Given I log in as "admin"
    And I have uploaded a schema with id "test.service"

  @javascript
  Scenario: Edit schema content
    Given I navigate to "Server > Web Service Manager > Dashboard" in site administration
    When I click on "Edit" "link" in the "test.service" "table_row"
    Then I should see "Edit Schema"
    And I should see "YAML Content"
    And I should see "test.service"

  @javascript
  Scenario: Save schema changes
    Given I navigate to "Server > Web Service Manager > Dashboard" in site administration
    When I click on "Edit" "link" in the "test.service" "table_row"
    And I set the field "YAML Content" to multiline:
      """
      meta:
        id: "test.service"
        name: "Updated Service"
        version: "1.0.0"
      definition:
        functions:
          - core_webservice_get_site_info
      """
    And I press "Save changes"
    Then I should see "was updated successfully"
    And I should see "Updated Service"

  @javascript
  Scenario: Cancel editing
    Given I navigate to "Server > Web Service Manager > Dashboard" in site administration
    When I click on "Edit" "link" in the "test.service" "table_row"
    And I press "Cancel"
    Then I should see "View Schema"
