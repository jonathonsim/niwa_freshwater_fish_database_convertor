# Convert the NIWA freshwater fish database into a format suitable for google maps

Why? Because I'm a weirdo and wanted to see the distribution of freshwater fish around me,
 and it was a rainy day :-) 


Note that there are other, much better ways to view this data using GIS software.  But google 
maps has a couple of advantages - mainly that it shows roads and satellite maps very well.



# How to use
1. Download the NIWA database from https://nzffdms.niwa.co.nz/search. Choose 'CSV' as the output format and leave the 
other search fields blank 
2. Install this code
````
git clone https://github.com/jonathonsim/niwa_freshwater_fish_database_convertor.git
cd niwa_freshwater_fish_database_convertor
composer install

./fish convert-csv-to-google-maps \
    tmp/nzffdms.csv #Path to the database
    tmp/ #Output directory
    --location=-36.6119003,174.8336885 #Optional - only process within 0.25 degree latitude/longitude of this location
    - radius=0.25 # Degrees radius around the location to process.  Speeds things up a lot.
    --file-per-species #Create a seperate output file per species
````
3. Go to google my maps https://www.google.com/mymaps and upload the files produced
4. when asked what field holds the location choose 'location' and choose 'Latitude, Longitude'.  
5. For a column to title the markers choose 'catchname'


# Notes
There's a lot of data here, over 130k rows.  

The hardest thing here is converting the NZMG coordinates used in the database ('easting' and 'northing') into
latitude and longitude.  We do this by using a port of the NIWA example C code to convert from NZMG to NZDG1949.  
The math of this is scary and these conversions take a long time (hence the --location option to localise the conversion).
It is done using a straightforward port of the C code found here https://www.linz.govt.nz/data/geodetic-services/download-geodetic-software

Note that this is probably out by some amount (200 meters?) as new zealand moves around a lot.  To convert to a true WS84 coordinate would take hours.


