Feature: Register
    As an anonymous
    In order to start using Gitonomy
    I should be able to register to the application

    Background:
        Given administrator has enabled registration
        Given user "tomoto" does not exist
        Given user "existing" exists

    Scenario: I should be able to register

        Given I am on "/"
          And I click on "Register"
         Then I should see a title "Register"
          And I should see a register form

        Given I fill:
            | Username | tomoto |
            | Fullname | Tomoto Pomo |
            | Email | tomoto@pomo.com |
            | Timezone | Europe/Paris |
            | Password | haricoo |
            | Confirm password | haricoo |
          And I click on "Register"
         Then I should see "Your account was created!"

    Scenario: I shouldn't be able to register with an e-mail already used

        Given I am on "/"
          And I click on "Register"
         Then I should see a title "Register"
          And I should see a register form

        Given I fill:
            | Username | tomoto |
            | Fullname | Tomoto Pomo |
            | Email | existing@example.org |
            | Timezone | Europe/Paris |
            | Password | haricoo |
            | Confirm password | haricoo |
          And I click on "Register"
         Then I should see "Roger, we have a problem with your form"
          And I should see "This e-mail is already present in our database"
