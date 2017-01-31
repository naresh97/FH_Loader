#FH Loader

##Description:
A quick and easy way to load resources from the FH websites (i.e. Stundenplan or ILIAS Mail)

##Usage:
```php
//Load the library into your code
require('FH_Loader.php')

//Initialize the library with FH login details (username in format aaXXXXs)
$fh = new FH_Loader("USERNAME", "PASSWORD");
```

###Function Reference###

* _FH\_Loader::loadCalc()_ - Load the calendar (Stundenplan) in the ICAL format
* _FH\_Loader::loadMails()_ - Load all ILIAS messages (includes message body)
* _FH\_Loader::loadMailsJson()_ - Load in JSON format
* _FH\_Loader::loadSpecificMail(URL)_ - Load the body of an ILIAS message
* _FH\_Loader::loadRegisteredGroups()_ - Load a list of all registered courses/groups
* _FH\_Loader::loadRegisteredGroupsJson()_ - Load the list in JSON format
* _FH\_Loader::loadPersonal()_ - Load the personal profile information of user
* _FH\_Loader::loadPersonalJson()_ - Load info in JSON format
* _FH\_Loader::loadCampusURL(URL)_ - Load any CampusOffice website which requires authorization
* _FH\_Loader::loadIliasURL(URL)_ - Load any ILIAS website which requires authorization (useful to parse ILIAS pages yourself)