#!/bin/sh

tarih=`date "+%Y%m%d-%H%M%S"`

# SECURITY: Hardcoded credentials moved to environment variables
# Set these environment variables before running the script:
# export FTP_HOST='your.ftp.server'
# export FTP_USER='your_username'
# export FTP_PASSWD='your_password'
# export FTP_SERVER='your_server_ip'

# Check if environment variables are set
if [ -z "$FTP_HOST" ] || [ -z "$FTP_USER" ] || [ -z "$FTP_PASSWD" ] || [ -z "$FTP_SERVER" ]; then
    echo "ERROR: FTP credentials not set in environment variables"
    echo "Please set: FTP_HOST, FTP_USER, FTP_PASSWD, FTP_SERVER"
    exit 1
fi

HOST="$FTP_HOST"
USER="$FTP_USER"
PASSWD="$FTP_PASSWD"
SERVER="$FTP_SERVER"

mkdir /var/mountftp
cd /var/mountftp

awk -f /sbin/dhcptibduzenle.sh < /var/dhcpd/var/db/dhcpd.leases > ./dhcplog$HOST-$tarih.txt

logger `ftp -n -v $SERVER << EOT
ascii
user $USER $PASSWD
prompt
put dhcplog$HOST-$tarih.txt
bye
EOT`

cd ..
rm -rf /var/mountftp