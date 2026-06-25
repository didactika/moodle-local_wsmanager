@local @local_servicemanager
Feature: Schema import
  In order to create web services from YAML
  As an administrator
  I need to import and process schema files

  Background:
    Given I log in as "admin"
    And I navigate to "Server > Web Service Manager > Dashboard" in site administration

  @javascript @_file_upload
  Scenario: Import a valid YAML schema file
    When I click on "Import Schemas" "link"
    And I upload "local/servicemanager/examples/sample_schema.yaml" file to "Import File" filemanager
    And I press "Import"
    Then I should see "Import complete"
    And I should see "Example Service"

  @javascript
  Scenario: Attempt import without a file
    When I click on "Import Schemas" "link"
    And I press "Import"
    Then I should see "Required"

  @javascript
  Scenario: View an imported schema's details
    Given I have uploaded a schema with id "test.service"
    And I reload the page
    When I click on "Test Service" "link"
    Then I should see "test.service"
    And I should see "Functions"
    And I should see "Back"
