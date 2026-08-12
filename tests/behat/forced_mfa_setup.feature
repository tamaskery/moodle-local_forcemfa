@local_forcemfa
Feature: Require an authenticated user to configure a genuine MFA factor
  In order to protect Moodle access without replacing authentication
  As a site administrator
  I need covered users to configure a genuine MFA factor before continuing

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And I log in as "admin"
    And the following config values are set as admin:
      | enabled | 1      | tool_mfa      |
      | enabled | 1      | factor_auth   |
      | weight  | 100    | factor_auth   |
      | goodauth| manual | factor_auth   |
      | enabled | 1      | factor_nosetup|
      | weight  | 100    | factor_nosetup|
      | enabled | 1      | factor_totp   |
      | weight  | 100    | factor_totp   |
      | policy  | 1      | local_forcemfa|
    And I log out

  Scenario: A covered user without a genuine factor is sent to MFA preferences
    When I am on the "Course 1" course page logged in as "student1"
    Then I should see "Multi-factor authentication preferences"
    And I should see "Before continuing, configure a multi-factor authentication method"

  Scenario: A configured factor permits normal access
    Given the following "tool_mfa > User factors" exist:
      | username | factor | label              |
      | student1 | totp   | Authenticator app  |
    When I am on the "Course 1" course page logged in as "student1"
    Then I should see "Course 1"
    And I should not see "Before continuing, configure a multi-factor authentication method"

  Scenario: Completing setup resumes the original deep link once
    Given I am on the "Course 1" course page logged in as "student1"
    And I should see "Multi-factor authentication preferences"
    And the following "tool_mfa > User factors" exist:
      | username | factor | label              |
      | student1 | totp   | Authenticator app  |
    When I reload the page
    Then I should see "Course 1"

  Scenario: A revoked factor does not satisfy the prerequisite
    Given the following "tool_mfa > User factors" exist:
      | username | factor | label             | revoked |
      | student1 | totp   | Revoked app       | 1       |
    When I am on the "Course 1" course page logged in as "student1"
    Then I should see "Multi-factor authentication preferences"

  Scenario: The disabled policy does not alter normal access
    Given I log in as "admin"
    And the following config values are set as admin:
      | policy | 0 | local_forcemfa |
    And I log out
    When I am on the "Course 1" course page logged in as "student1"
    Then I should see "Course 1"

  Scenario: A site administrator is exempt only in the except-administrators mode
    When I log in as "admin"
    Then I should not see "Before continuing, configure a multi-factor authentication method"
    And the following config values are set as admin:
      | policy | 2 | local_forcemfa |
    When I am on site homepage
    Then I should see "Multi-factor authentication preferences"
