# Convert the NIWA freshwater fish database into a format suitable for google maps

Why? Because I'm a weirdo and wanted to see the distribution of freshwater fish around me :-) 


Note that there are other, much better ways to view this data using GIS software.  But google 
maps has a couple of advantages - mainly that it shows roads and satellite maps very well.



# How to use
- Download the NIWA database from https://nzffdms.niwa.co.nz/search. Choose 'CSV' as the output format and leave the 
other search fields blank 

````
git clone https://github.com/jonathonsim/niwa_freshwater_fish_database_convertor.git
cd niwa_freshwater_fish_database_convertor
composer install

./fish convert-csv-to-google-maps \
    tmp/nzffdms.csv #Path to the database
    tmp/ #Output directory
    --location=-36.6119003,174.8336885 #Optional - only process within 1degree latitude/longitude of this location
    --file-per-species #Create a seperate output file per species
````
- Go to google my maps https://www.google.com/mymaps and upload the files produced
- when asked what field holds the location choose 'location' and choose 'Latitude, Longitude'.  
- For a column to title the markers choose 'catchna,e'


# Notes
There's a lot of data here, over 130k rows.  

The hardest thing here is converting the NZMG coordinates used in the database ('easting' and 'northing') into
latitude and longitude.  We do this by using a port of the NIWA example C code to convert from NZMG to NZDG1949.  
The math of this is scary and these conversions take a long time (hence the --location option to localise the conversion)

Note that this is probably out by some amount (meters?) as there's lot of differences between this and WSG84 data
(new zealand moves around a lot)


