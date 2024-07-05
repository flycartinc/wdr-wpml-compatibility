#!/bin/bash

# Author: Anantharaj B
# Commends: build

check_dependency() {
    if [ ! -x "$(command -v wp)" ]; then
        echo "wp-cli not installed! Aborted."
        exit 1
    fi

    if [ ! -x "$(command -v composer)" ]; then
        echo "composer not installed! Aborted."
        exit 1
    fi

    if [ ! -x "$(command -v zip)" ]; then
        echo "zip not installed! Aborted."
        exit 1
    fi
}

rename_files() {
    if [ ! -z "$2" ]; then
        find . -type f -name "*$1*" | while read FILE; do
            NEW_FILE="$(echo ${FILE} | sed -e 's|'"$1"'|'"$2"'|')";
            mv "${FILE}" "${NEW_FILE}";
        done
    fi
}

replace_text_in_files() {
    if [ ! -z "$2" ]; then
        find . -type f -exec sed -i -e 's|'"$1"'|'"$2"'|g' {} +
    fi
}

run_composer_update() {
    echo ''
    echo 'Updating composer packages...'
    composer update -q --no-dev
    echo 'Composer packages updated.'
}

generate_pot_file() {
    echo ''
    echo 'Generating plugin POT file...'

    wp i18n make-pot . i18n/languages/"$PLUGIN_SLUG".pot --quiet --slug="$PLUGIN_SLUG"

    cd i18n/languages/
    replace_text_in_files 'FULL NAME' "Flycart"
    replace_text_in_files 'EMAIL@ADDRESS' "support@flycart.org"
    replace_text_in_files 'LANGUAGE <LL@li.org>' "Flycart <support@flycart.org>"
    replace_text_in_files 'YEAR-MO-DA HO:MI+ZONE' " "
    cd ../..

    echo 'POT file Generated.'
}

export_zip_file() {
    echo ''
    echo 'Exporting plugin files to Zip...'

    cd .temp
    zip -r -q "../$PLUGIN_SLUG".zip *
    cd ..

    echo 'Zip Export Completed.'
}

build() {
    check_dependency

    PLUGIN_NAME="Discount Rules - WPML Compatibility"
    PLUGIN_SLUG="wdr-wpml-compatibility"

    if [ -f "$PLUGIN_SLUG".zip ]; then
        rm "$PLUGIN_SLUG".zip
    fi

    if [ -d ".temp" ]; then
        rm -rf .temp
    fi

    run_composer_update

    mkdir -p .temp/"$PLUGIN_SLUG"
    cp -r * .temp/"$PLUGIN_SLUG"
    cd .temp/"$PLUGIN_SLUG"

    rm plugin.sh
    rm composer*
    rm *.zip

    generate_pot_file

    cp i18n/languages/"$PLUGIN_SLUG".pot ../../i18n/languages/"$PLUGIN_SLUG".pot

    cd ../..

    export_zip_file

    rm -r .temp

    echo ''
    echo "Plugin is built successfully!"
}

echo '|=============== WPML addon plugin builder ==============|'
echo '| Commends: build                                        |'
echo '|========================================================|'
if [ -z "$1" ]; then
    echo ''
    echo 'Argument required.'
    exit 1
elif [ "$1" = "build" ]; then
    build $2
else
    echo ''
    echo 'Invalid Argument.'
    exit 1
fi