# Simulated AWS EC2 metadata service for Docker

Unlike AWS API keys, AWS STS credentials are short-lived, cached, and updated automatically when they expire.

The recommended way to authenticate with AWS when running on EC2 is to use the built-in metadata service to obtain
STS credentials by assuming an IAM role that is granted to that machine.

I needed a way to simulate this setup for development and testing purposes in my local environment. This service
simulates the Amazon Web Services EC2 metadata service so that Docker containers can assume an IAM role as if they
were EC2 instances.

## :warning: Security Warning

Like the metadata service on Amazon EC2 instances, no authentication is provided.

This means that any process that is able to access this service will be able to obtain credentials.

**This service should never be run on any publicly available host or in any production environment!**

Always run this service on a dedicated Docker network, and only add containers to the network which should be allowed to assume IAM roles.

## Metadata Service Container Configuration

The following settings are required for proper operation of the metadata service container.

### Volumes

Host                 | Container            | Required?
---------------------|----------------------|------------
/var/run/docker.sock | /var/run/docker.sock | Required
~/.aws               | /root/.aws           | Recommended

The docker.sock volume enables the metadata service container to inspect the environment of calling service containers,
to determine the IAM role ARN to assume for each container.

### Networks

The metadata service container requires a dedicated external network in bridge mode and a specific IP address.

Setting    | Value            | Explanation
-----------|------------------|------------------------------------------------------
Name       | User defined      | Set to anything you like, such as `ec2_metadata`
Type       | External         | Required to allow setting the required IP address
Mode       | Bridge           | Docker network mode, required to allow setting IP
Subnet     | 169.254.169.0/24 | AWS hard-coded subnet of the EC2 metadata service
IP address | 169.254.169.254  | AWS hard-coded IP address of the EC2 metadata service

This network must be created in advance by running a command such as the following:

    $ docker network create -d bridge --subnet 169.254.169.0/24 ec2_metadata

> :warning: Never add any containers to this network that should NOT be allowed to assume an IAM role!

### Environment

At least one of the following environment variables will be required to be set on the metadata service container.

Environment Variable    | Description                                                   | Required?
------------------------|---------------------------------------------------------------|-----------------
`DEFAULT_IAM_ROLE`      | Full ARN to assume for containers that have no `IAM_ROLE` set | Optional
`AWS_PROFILE`           | Profile name to use in the shared config and credentials files   | Recommended
`AWS_ACCESS_KEY_ID`     | AWS access key                                                | Not recommended
`AWS_SECRET_ACCESS_KEY` | AWS secret key                                                | Not recommended
`AWS_REGION`            | AWS region                                                    | Not recommended

The default IAM role will be used if no `IAM_ROLE` environment variable is set on the calling service container.
Alternately, the default role can be specified on the command line:

    $ bin/docker-ec2-metadata [default-role-arn]

Some AWS credentials and the AWS region are required. The recommended way to provide these is by mounting
the shared credentials and config files in `~/.aws` to the container (see [Volumes](#volumes) above),
in conjuction with the `AWS_PROFILE` environment variable to select the desired profile.

To set up your shared credentials and config files, run the following command:

    $ aws configure --profile=your-profile-name

## Calling Service Container Configuration

### Networks

In addition to any other networks used by the calling service container, the container must also be added to
the dedicated `metadata` network created above for the metadata service container.

To allow accessing the container, specify the network under the `networks` directive in `docker-compose.yml`:

```
version: '3.7'

services:
  metadata:
    ...
    networks:
      metadata:
        ipv4_address: 169.254.169.254
  app:
    ...
    networks:
      - default
      - metadata

networks:
  default:
    name: my_network
  metadata:
    name: ec2_metadata
    external: true
```

For a complete working example, see [docker-compose.yml](docker-compose.yml).

### Environment

Environment Variable    | Description                                                   | Required?
------------------------|---------------------------------------------------------------|-----------------
`IAM_ROLE`              | IAM role to assume (specify the full ARN)                     | Recommended

If not set, the default role will be used (see above).

## Testing

To test that it works:

1. Set up your AWS shared config and credentials files:

    ```
    $ aws configure --profile=your-profile-name
    ```

2. Create a `.env` file in the project root, specifying the desired profile and default IAM role:

    ```
    $ echo "AWS_PROFILE=my_app" >> .env
    $ echo "IAM_ROLE=arn:aws:iam::114047350549:role/tys-ec2-staging" >> .env
    ```

3. Create the external dedicated metadata network:

    ```
    $ docker network create -d bridge --subnet 169.254.169.0/24 ec2_metadata
    ```

4. Build the docker images:

    ```
    $ docker-compose build
    ```

5. Start the service:

    ```
    $ docker-compose up
    ```

You should see output like the following:

```
docker-ec2-metadata-metadata-1  | [notice] Listening on 169.254.169.254:80
docker-ec2-metadata-metadata-1  | [notice] GET: /latest/meta-data/iam/security-credentials/dev (from 169.254.169.2) (container=docker-ec2-metadata-test_app-1 / role=arn:aws:iam::114047350549:role/tys-ec2-staging) (curl/7.81.0)
docker-ec2-metadata-test_app-1  | {"AccessKeyId":"ASIA...","SecretAccessKey":"...","Token":"...","Code":"Success","Type":"AWS-HMAC","Expiration":"2023-08-03T17:39:55+00:00","LastUpdated":"2023-08-03T16:39:55+00:00"}
docker-ec2-metadata-test_app-1 exited with code 0
```

At this point, you can press CTRL+C to exit and shut down the metadata service.

## License

MIT License

## Credits

This project was inspired by and patterned after https://github.com/Nosto/metadata-credentials-proxy.
It was rewritten by Jonathon Hill in PHP from the Go source code to enable reading shared AWS config
and credentials files, which the AWS SDK for PHP appears to do a better job of supporting (as of August 2023).
