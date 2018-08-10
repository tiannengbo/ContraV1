#!/bin/bash

if [ "$USER" != "root" ];then
    echo "sudo run the script"
    exit 1
fi

if [ "$#" -eq "3" ];then
	rm /etc/network/interfaces -rf
	echo "auto eth0" >>/etc/network/interfaces
	echo "iface eth0 inet static" >>/etc/network/interfaces
	echo "address $1" >>/etc/network/interfaces
	echo "netmask $2" >>/etc/network/interfaces
	echo "gateway $3" >>/etc/network/interfaces
	echo -e "dns-nameservers 114.114.114.114\n" >>/etc/network/interfaces

	echo "auto lo" >>/etc/network/interfaces
	echo "iface lo inet loopback" >>/etc/network/interfaces
elif [ "$#" -eq "4" ];then
	rm /etc/network/interfaces -rf
	echo "auto eth0" >>/etc/network/interfaces
	echo "iface eth0 inet static" >>/etc/network/interfaces
	echo "address $1" >>/etc/network/interfaces
	echo "netmask $2" >>/etc/network/interfaces
	echo "gateway $3" >>/etc/network/interfaces
	echo -e "dns-nameservers $4\n" >>/etc/network/interfaces

	echo "auto lo" >>/etc/network/interfaces
	echo "iface lo inet loopback" >>/etc/network/interfaces
else
	cp /home/orangepi/share/outputdash/bin/orgminer/interfaces /etc/network/ -f
fi

sync
/etc/init.d/networking restart
/sbin/ifup wlan0
/sbin/ifup eth0
