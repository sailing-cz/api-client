.PHONY:
	all

all: test

clean:
	chown -R virtual.virtual vendor/
	rm -rf temp/*

update: clean
	composer update

test:
	vendor/bin/tester tests