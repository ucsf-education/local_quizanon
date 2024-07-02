@local @local_quizanon
Feature: Basic use of the local_quizanon reports
  In order to see if the local_quizanon plugin is working

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | T1        | Teacher1 | teacher1@example.com |
      | student1 | S1        | Student1 | student1@example.com |
      | student2 | S2        | Student2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "activities" exist:
      | activity   | name   | intro              | course | idnumber | groupmode |
      | quiz       | Quiz 1 | Quiz 1 description | C1     | quiz1    | 2         |
    And the following "questions" exist:
      | questioncategory | qtype       | name  | questiontext         |
      | Test questions   | description | Intro | Welcome to this quiz |
      | Test questions   | truefalse   | TF1   | First question       |
      | Test questions   | truefalse   | TF2   | Second question      |
      | Test questions   | essay       | Essay | Essay question       |
    And quiz "Quiz 1" contains the following questions:
      | question | page | maxmark |
      | Intro    | 1    |         |
      | TF1      | 1    |         |
      | TF2      | 1    | 3.0     |
      | Essay    | 1    |         |
    And user "student1" has attempted "Quiz 1" with responses:
      | slot | response |
      |   2  | True     |
      |   3  | False    |
      |   4  | "This is a test essay" |
    And user "student2" has attempted "Quiz 1" with responses:
      | slot | response |
      |   2  | True     |
      |   3  | True     |
      |   4  | "This is a test essay" |

  @javascript
  Scenario: Teacher should see user's name in the grades report
    Given Quizanon plugin is disabled for quiz "Quiz 1"
    And I am on the "Quiz 1" "quiz activity" page logged in as teacher1
    And I navigate to "Results" in current page administration
    Then I should see "Student1"
    And I should see "Student2"

  @javascript
  Scenario: Teacher should see user's name in the grades report when set role is editingteacher
    Given "editingteacher" is excluded from quizanon for quiz "Quiz 1"
    And I am on the "Quiz 1" "quiz activity" page logged in as teacher1
    And I navigate to "Results" in current page administration
    Then I should see "Student1"
    And I should see "Student2"

  @javascript
  Scenario: Teacher should not see user's name in the grades report
    Given Quizanon plugin is enabled for quiz "Quiz 1"
    And I am on the "Quiz 1" "quiz activity" page logged in as teacher1
    And I navigate to "Results" in current page administration
    Then I should not see "Student1"
    And I should not see "Student2"

  @javascript
  Scenario: Teacher should see user's name in the responses report
    Given Quizanon plugin is disabled for quiz "Quiz 1"
    And I am on the "Quiz 1" "mod_quiz > Responses report" page logged in as teacher1
    Then I should see "Student1"
    And I should see "Student2"

  @javascript
  Scenario: Teacher should see user's name in the responses report when set role is editingteacher
    Given "editingteacher" is excluded from quizanon for quiz "Quiz 1"
    And I am on the "Quiz 1" "mod_quiz > Responses report" page logged in as teacher1
    Then I should see "Student1"
    And I should see "Student2"

  @javascript
  Scenario: Teacher should not see user's name in the responses report
    Given Quizanon plugin is enabled for quiz "Quiz 1"
    And I am on the "Quiz 1" "mod_quiz > Responses report" page logged in as teacher1
    Then I should not see "Student1"
    And I should not see "Student2"

  @javascript
  Scenario: Teacher should see user's name in the grades report
    Given Quizanon plugin is disabled for quiz "Quiz 1"
    And I am on the "Quiz 1" "mod_quiz > Manual grading report" page logged in as teacher1
    And I click on "grade" "link" in the "Essay" "table_row"
    Then I should see "Student1"
    And I should see "Student2"

  @javascript
  Scenario: Teacher should see user's name in the grades report when set role is editingteacher
    Given "editingteacher" is excluded from quizanon for quiz "Quiz 1"
    And I am on the "Quiz 1" "mod_quiz > Manual grading report" page logged in as teacher1
    And I click on "grade" "link" in the "Essay" "table_row"
    Then I should see "Student1"
    And I should see "Student2"

  @javascript
  Scenario: Teacher should not see user's name in the grades report
    Given Quizanon plugin is enabled for quiz "Quiz 1"
    And I am on the "Quiz 1" "mod_quiz > Manual grading report" page logged in as teacher1
    And I click on "grade" "link" in the "Essay" "table_row"
    Then I should not see "Student1"
    And I should not see "Student2"
