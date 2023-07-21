# Simulated AWS EC2 metadata service for Docker

This service simulates the Amazon Web Services EC2 metadata service so that
Docker containers can assume an IAM role as if they were EC2 instances.

The metadata API responds to the following queries:

* http://169.254.169.254/latest/meta-data/iam/security-credentials
* http://169.254.169.254/latest/meta-data/iam/security-credentials/dev

STS credentials are short-lived, cached, and updated automatically when they expire.

## Security

Like the metadata service on Amazon EC2 instances, no authentication is provided.
Any process that is able to access this service will be able to obtain credentials.

**This service should not be run on any publicly available host or in any production environment.**

## Configuration

The AWS credentials used to get STS assumed role credentials must be provided in
environment variables or `~/.aws/config`.

The IAM role to assume is determined by looking at the env variable `IAM_ROLE`
of the requesting Docker container. If this variable cannot be found, the default
role is used, which can be specified as the `DEFAULT_IAM_ROLE` environment
variable, or as a command line parameter to the `metadata` binary.

> Note: the full role ARN must be specified, not just the role name.

## Usage

The metadata service needs to run inside Docker to be able to inspect the
environment variables of other containers. For this it also needs access to `docker.sock`.

The metadata service must be run with a specific IP: `169.254.169.254`. Specifying static
IP addresses for Docker containers requires an external network. Create one for this:

    $ docker network create -d bridge --subnet 169.254.169.0/24 ec2_metadata

To build and start the container, run:

    $ docker-compose up

To give other Docker containers access to the metadata service, specify their IAM
role and grant them access to the network in `docker-compose.yml`:

```
version: "2"

services:
  app:
    build: .
    environment:
      - 'IAM_ROLE=arn:aws:iam::1234567890:role/example_role'
    networks:
      - metadata

networks:
  metadata:
    name: ec2_metadata
    external: true
```

## Testing

To test that it works, first start the service:

    $ docker-compose up --detach

Then start a new container on the `ec2_metadata` network (substitute your actual role ARN):

    $ docker run --network ec2_metadata --env IAM_ROLE=arn:aws:iam::1234567890:role/example_role -it ubuntu bash
    $ apt-get update && apt-get install -y curl

Finally, query the service:

    $ curl http://169.254.169.254/latest/meta-data/iam/security-credentials/dev
