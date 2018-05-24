# Moo Development Console

Commands to help you manage local development environment.

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg?style=flat-square)](https://php.net/)

Installation
--------------------

```bash
$ wget https://github.com/satrun77/moo-command/releases/download/v1.0.0-alpha4/moo.phar

$ chmod +x moo.phar

$ mv moo.phar /usr/local/bin/moo
```

Development
--------------------

- Clone the source code.
- Run the installer from the root directory of the source code `./install.sh`

> Note: the installer is going to install [pharcc](https://github.com/cbednarski/pharcc/releases/download/v0.2.3/pharcc.phar), if it does not exists.

Usage
--------

#### Available commands: 
Name | Details
------------ | -------------
  commit       |Git Commit wrapper to standardise the commit messages.
  csfixer      |Execute php-cs-fixer on selected paths.
  help         |Displays help for a command
  list         |Lists commands
  qcode        |Check source code using tool such as, Mess Detector, Copy/Paste Detector, PHP Parallel Lint, & Security Advisories.
  faq          |Display FAQs.
  ws:build     |Build or rebuild services for a site within the workspace. A wrapper to docker-compose build command.
  ws:clean     |Execute specific commands to free up unwanted space such as, removing old containers, or dangling images.
  ws:clone     |Setup local development from a single YAML file within a repository (.env.yml)
  ws:composer  |Execute PHP composer command inside the composer container.
  ws:cp        |Copy a file from host machine to a docker container or download from a docker container. A wrapper to docker cp command.
  ws:hosts     |Update the host file in user machine (/etc/hosts) with the docker IP address and the host names setup for all of the sites in workspace.
  ws:ip        |Display the docker machine IP address.
  ws:ips       |Display the IP addresses selected for each of the active docker containers.
  ws:log       |View output from container or containers. A wrapper for docker-compose logs.
  ws:new       |Create a new site. Create all of the files needed for the docker containers.
  ws:proxy     |Build the proxy container if not exists or start the container.
  ws:rm        |Remove stopped containers for a site within the workspace. A wrapper for docker-compose rm.
  ws:sake      |Execute SilverStripe Sake command inside the php container.
  ws:sites     |Display list of available sites and their statuses.
  ws:ssh       |SSH into a container for a site within the workspace.
  ws:start     |Create and start containers for a site within the workspace. A wrapper to docker-compose up.
  ws:stat      |Display a live stream of container(s) resource usage statistics.
  ws:stop      |Stop services for a site within the workspace. A wrapper to docker stop command.

License
-------

Moo Command is licensed under the [MIT License](LICENSE.md).

