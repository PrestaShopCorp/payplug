# The module name for Prestashop
MODULE_NAME=payplug
# We'll need to add a slash to store the tracked file inside a new directory, as Prestashop expects
PREFIX=$(addsuffix /, $(MODULE_NAME))
ARCHIVE_NAME=$(addsuffix .zip, $(MODULE_NAME))

# $^ will be the file where to find the version, interpreted as a dependancie
# You might want to hardcode a path to the file payplug.php instead
# We will first get the line containing the version number, and the extract it
# It should grep version of the form "3.1415.9", that is only digits and dots
VERSION=$(shell grep "this->version =" $^ | pcregrep -o "\d[0-9.a-zA-Z]*")

#Use git built in tool to archive all tracked files
archive: archive_master

# payplug.php is a dependency needed to retrieve version
version: payplug.php
	@printf "Archiving version : %s \n" $(VERSION)

archive_master: version
	@printf "Archiving master \n"
	git archive --format=zip --prefix=$(PREFIX) master > $(ARCHIVE_NAME)

archive_head: version
	@printf "Archiving current head. Remember to commit your changes if you want them in the resulting archive \n"
	git archive --format=zip --prefix=$(PREFIX) HEAD > $(ARCHIVE_NAME)

clean:
	rm -v $(ARCHIVE_NAME)

.PHONY: archive archive_master archive_head clean version
