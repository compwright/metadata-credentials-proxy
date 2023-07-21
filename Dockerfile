FROM golang:1.20 as builder

## GOLANG env
ARG GOPROXY="https://proxy.golang.org|direct"
ARG GO111MODULE="on"

# Copy go.mod and download dependencies
WORKDIR /root
COPY go.mod .
COPY go.sum .
RUN go mod download

## GOLANG env
ARG CGO_ENABLED=0
ARG GOOS=linux
ARG GOARCH=amd64

# Build
COPY . .
RUN make

# Build the final image with only the binary
FROM alpine
WORKDIR /root
COPY --from=builder /root/metadata .
COPY LICENSE.txt .
ENTRYPOINT ["/root/metadata"]
