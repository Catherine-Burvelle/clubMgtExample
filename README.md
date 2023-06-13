# clubMgtExample
Sample of code from clubMgt.
clubMgt is a module for PhpBoost framework.

# Purpose of this repository
This sample of code is to illustrate the use of implementation and heritage.
The purpose of this code is to propose a mass mailing interface. The graphical interface, and the behavior are adapted based on the category of people you are targetting:
If the target is a lesson (persons attended a lesson), you will be able to include the schedule and location of lessons for each recipient.
If the target is the responsible, you will be able to include the list of children of responsible in the message,
and so on.

# Architecture of the code
This is a MVC code.
The main controller is MailerController
It is using objects with MailSpecification interface.
And MailForXXX classes implement this interface, either directly or through the heritage.

# Full code is about
* 30 000 lines of php code
* 1 000 lines of JS
* 63 view templates
