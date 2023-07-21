PACKAGE = metadata

all: build

build:
	go build -o metadata

format:
	gofmt -w *.go
