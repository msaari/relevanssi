#!/bin/sh

# Test runner for running Phpunit tests against multiple WordPress versions.
# Will fetch and install different WordPress versions in this directory:

BASE_PATH=~/multiwptest

if [ ! -d $BASE_PATH ]
then
	echo "ERROR: Test directory $BASE_PATH is missing."
	exit 1
fi

if [ ! -f "$BASE_PATH/wp-tests-config.php" ]
then
	echo "ERROR: $BASE_PATH/wp-tests-config.php is missing."
	exit 1
fi

# Minimum version number to test:

MINIMUM_VERSION=5.3.6

# Fetch the available WordPress versions.

echo "FETCHING AVAILABLE WP VERSIONS WITH A MINIMUM OF $MINIMUM_VERSION:"
VERSIONS="$(php wpversion.php $MINIMUM_VERSION)"
echo "- found $VERSIONS"

# Store existing WP_TESTS_INSTALLATION setting so it can be restored and
# remember where we are.

ORIGINAL_WP_TESTS_INSTALLATION=$WP_TESTS_INSTALLATION
ORIGINAL_DIR=$PWD

for version in $VERSIONS
do
	TEST_VERSION=true
	echo "\nTESTING $version:"
	echo "- see if dir exists"

	if [ -d "$BASE_PATH/wordpress-$version" ]
	then
		echo "- exists!"
	else
		echo "- doesn't exist, fetching..."
		cd $BASE_PATH
		wget -nv https://wordpress.org/wordpress-$version.zip
		if [ -f "wordpress-$version.zip" ]
		then
			echo "- fetched, unzipping"
			mkdir tmp
			unzip -q wordpress-$version.zip -d tmp
			echo "- rename"
			mv tmp/wordpress wordpress-$version
			rm -rf tmp
			echo "- copy wp-tests-config.php"
			cp wp-tests-config.php wordpress-$version
			echo "- done!"
		else
			echo "- couldn't fetch, skipping this version"
			TEST_VERSION=false
		fi
	fi

	if [ $TEST_VERSION ]
	then
		export WP_TESTS_INSTALLATION=$BASE_PATH/wordpress-$version
		cd $ORIGINAL_DIR

		if [ -f "multisite.xml" ] && [ ${version:0:1} == "5" ] && [ ${version:2:1} != "0" ]
		then
			phpunit -c multisite.xml --stop-on-failure
		else
			phpunit --stop-on-failure
		fi
	else
		echo "- skipping version"
	fi
done

export WP_TESTS_INSTALLATION=$ORIGINAL_WP_TESTS_INSTALLATION