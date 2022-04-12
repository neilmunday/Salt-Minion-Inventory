# Change Log

## Version 1.7.1 (2022-04-12)

* Updated moment.js version to 2.29.2 to address CVE-2022-24785

## Version 1.7 (2020-11-03)

* Updated to work on Windows minions for issue #30
* Corrected typo in inventory module for disks dictionary - thanks to Jonathan and @seamus-45 for spotting this
* Merged pull request #34 from @seamus-45 to fix bug in inventory runner audit function for issue #33

## Version 1.6 (2019-06-28)

* Updated to work with Python 3 version of SaltStack

## Version 1.5 (2019-04-24)

* Merged pull request #20 from @dajamu to add improved user interface (issues #13 and #18)
* Added GPU quantities for issue #17
* Disable minion info tabs that have no content for issue #19
* Added minion serial number, model and vendor for issue #24
* Added minion boot time for issue #23

## Version 1.4 (2019-04-01)

* Added recording of graphics cards for each minion for issue #11
* Updated to work with SaltStack 2019 - issue #14
* Added recording of users logged into each minion - issue #10
* Bug fix for UTC date handling - issue #15
* Added recording of disks attached to each minion - issue #9

## Version 1.3 (2018-12-21)

* Bug fix for minions that do not have selinux installed (issue #8)
* Bug fix for minions using SaltStack Python3 version (commit #9f0e605011e869a77ec63865712632e297011a99)

## Version 1.2 (2018-11-28)

* Several improvements and bug fixes
* Ability to diff packages on two minions
* New minion info page

## Version 1.1 (2018-11-18)

* Bug fix for addition of new minions

## Version 1.0 (2018-11-18)

Initial release
