FROM golang:1.20 as builder

## GOLANG env
ARG GOPROXY="https://proxy.golang.org|direct"
ARG GO111MODULE="on"

RUN mkdir /opt/metadata
COPY Makefile metadata_wrapper_linux.sh /opt/metadata/
WORKDIR /opt/metadata

COPY main.go go.mod go.sum /opt/metadata/
## GOLANG env
ARG CGO_ENABLED=0
ARG GOOS=linux
ARG GOARCH=amd64


RUN make build && cp metadata /usr/bin/
