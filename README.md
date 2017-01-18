#FH Loader

##Description:
A quick and easy way to load resources from the FH websites (i.e. Stundenplan or ILIAS Mail)

##Usage:
Load the library into your code
```
require('FH_Loader.php')
```

Initialize the library
```
$fh->new FH_Loader("USERNAME", "PASSWORD");
```

The username is usually in the format of : xx12345s

Load the Stundenplan in iCal format:
```
$fh->loadCal();
```

Load ILIAS mails
```
$fh->loadMailsJson();
```

Load a certain page on CampusOffice
```
fh->loadCampusURL("URL");
```

Load a certain page on ILIAS
```
fh->loadIliasURL("URL");
```