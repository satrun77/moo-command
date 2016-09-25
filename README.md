# Moo Development Console (Development in progress)

Commands to help you manage local development environment.

Installation
--------------------

- Download the source code.
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
  qcode        |Code quality report.
  ws:build     |Build a container or all of the containers within a site. A wrapper to docker-compose build command.
  ws:clean     |Execute specific commands to free up unwanted space such as, removing old containers, or dangling images.
  ws:clone     |Clone repository and setup docker environment.
  ws:composer  |Execute composer command inside composer container container.
  ws:cp        |Copy a file from host machine to a docker container or download from docker container.
  ws:faq       |Display FAQs.
  ws:hosts     |Update the host file in user machine (/etc/hosts).
  ws:ip        |Display the docker machine ip address.
  ws:ips       |Display containers ip addresses.
  ws:log       |Display the logs of a container or all containers. A wrapper for docker-compose logs.
  ws:new       |Create a new site. Create all of the files needed for the docker containers.
  ws:proxy     |Build if not exists or start the proxy container.
  ws:rm        |Remove all of the containers with a site. A wrapper for docker-compose rm.
  ws:sake      |Execute SilverStripe Sake command inside the php container.
  ws:sites     |Display list of available sites and their exposed port.
  ws:ssh       |SSH into a container.
  ws:start     |Start a site. A wrapper to docker-compose up.
  ws:stop      |Stop a container or all of the containers within a site. A wrapper to docker stop command.
  ws:update    |Update site containers except for the directories site, env, solr/myindex.

License
-------

Moo Command is licensed under the [MIT License](LICENSE.md).
