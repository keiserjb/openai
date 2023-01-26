# OPENAI DRUPAL MODULE

## INTRODUCTION

The Drupal OpenAI module makes it possible to interact with the
[OpenAI API](https://openai.com/) to generate coherent content.

## REQUIREMENTS

This module is tested on Drupal 9.4 and above.

You need the default **Article** content type in order to generate content.

## INSTALLATION


### MANUAL INSTALLATION

1. Download the Drupal module with composer on the module page on [https://www.drupal.org/project/openai_api](https://www.drupal.org/project/openai_api).
2. Install with drush or in the Back office `admin/modules`.

## CONFIGURATION

The OpenAI API module settings is located at
`admin/config/system/api/openai/settings`, and can be accessed from a tab
under the Web services settings page.

After saving Token and Url and adding some taxonomy term in OpenAI subject
vocabulary, you can begin to generate articles with the form located at
`admin/config/system/article-generation`.

## DRUSH COMMAND EXAMPLE
1. drush openai:generate-article OR drush oga
2. drush openai:generate-media OR drush ogm
Then let yourself be guided by the interactive form
