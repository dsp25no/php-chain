.PHONY: all
all: build

.PHONY: dev
dev: dev_build
	docker run -ti --rm -v `pwd`/res:/res -v `pwd`/php-chain/lib:/php-chain/lib php-chain_dev bash

.PHONY: dev_build
dev_build:
	docker build -f php-chain/Dockerfile.dev -t php-chain_dev php-chain

.PHONY: build
build:
	docker build -t php-chain php-chain
