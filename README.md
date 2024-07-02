# Quiz Anonymizer Local Plugin

![GitHub Workflow Status (branch)](https://img.shields.io/github/actions/workflow/status/ucsf-education/local_quizanon/ci.yml?label=ci&branch=MOODLE_401_STAGING)

## Upgrading to new Moodle versions.

This plugin makes use of the CI/CD capabilities implemented by [Catalyst Moodle Workflows](https://github.com/catalyst/catalyst-moodle-workflows). It relies solely in the `version.php` file to analyze and run automated tests for the various supported Moodle versions.

To see if the plugin supports a specific Moodle version, you need to do the following:

1. Create a new branch.
```shell
git checkout -b MyBranch
```
2. Update the supported Moodle versions in the `version.php` file. For example we want to add support for Moodle 4.2 and 4.3, the current Moodle supported version is the 4.1 version.
```php
$plugin->supported = [401, 401];
```
We need to change this like so:
```php
$plugin->supported = [401, 403];
```
Now, the automated tests will run for all versions between 4.1 and 4.3 inclusively, which would be 4.1, 4.2 and 4.3. Once the `version.php` file is ready, save your changes and add them to your branch.
```shell
git add version.php # Add the version.php file
git commit -m"Adding support for new Moodle versions 4.2 and 4.3" # Add a commit with a descriptive comment.
git push origin MyBranch
```
3. Check the workflow status in the [GitHub actions tab](https://github.com/ucsf-education/local_quizanon/actions). There, you should see a workflow running with your commit message. You can review the build messages to debug the problems found.
4. Make the necessary changes to be able to fix the problems found in your build. You'll need to commit and push those changes in order for the CI workflows to run again.