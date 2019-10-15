# TCB Auth Client
Drupal module providing user authentication from site with tcb_auth_server 
module.

## Installation

To install the module, first install the following dependencies with their
corresponding versions:

- [Social API](https://www.drupal.org/project/social_api) (8.x-2.0-rc1)
- [Social Auth](https://www.drupal.org/project/social_auth) (8.x-2.0-rc1)
- [Social Auth Google](https://www.drupal.org/project/social_auth_google) (8.x-2.0-beta4)

Follow the instructions that can be found in the following link to setup
Social Auth Google to talk with Google's OAuth API: 
https://www.drupal.org/docs/8/modules/social-api/social-api-2x/social-auth-2x/social-auth-google-2x-installation

Once you have that all setup, you may install TCB Auth Client. After installation,
visit the TCB Client config form (under system in the config menu) and put in the 
domain name of your TCB site, sub-domain included. For example: 
dev-tcb-server.pantheonsite.io

Save the information once you have entered it, and then you are ready to go 
assuming everything was setup correctly. Simply visit the /user/login/google
path on your TCB Client site and it will create an account for you and sign
you in, so long as your email domain is in the list of valid domains from
TCB Server.
