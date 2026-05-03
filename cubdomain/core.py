#!/usr/bin/env python3

""" 
  Copyright (C) 2021 Semi-Auto bot tool 
  made by c0del1ar a.k.a Arya Kresna and it is licensed
"""


from re import findall
from datetime import date,timedelta
from time import sleep
import json, socket
from requests import get
from config import *
from random import shuffle
from tqdm import tqdm


def grabber(server=""):
  import tool.grab as grab
  
  if server == "1":
    print(f"{Color.default}{prefix}@ Start using server 1")
    sleep(1)
    print(f"{Color.yellow}{prefix}i ",end="")
    
    for char in grab.Service1().about:
      print(char,end="",flush=True)
      sleep(0.005)
      
    print(Color.default)
    date_input = False
    while date_input == False:
        fromdate = grab.inputdate("From")
        untildate = grab.inputdate("Until")
        try:
            start_date = date(int(fromdate[2]),int(grab.monthstrtoint(fromdate[1])),int(fromdate[0]))
            end_date = date(int(untildate[2]),int(grab.monthstrtoint(untildate[1])),int(untildate[0]))
            delta = timedelta(days=1)
            date_input = True
        except Exception:
            print(f"{Color.red}{prefix}x Error, please input date again{Color.default}")
            date_input = False
        except ValueError:
            print(f"{Color.red}{prefix}x Date not found{Color.default}")
            date_input = False
        except KeyboardInterrupt:
            print(f"{prefix} Cancelling..")

    extension = []
    forext = False
    totalresult = []
    
    while forext == False:
      forext = input(f"{Color.blue}{prefix}? Interesting to targetting extension? (Y/n): {Color.default}")
      
      if forext == "Y" or forext == "y":
        forext,targeting = (True,True)
        
      elif forext == "N" or forext == "n":
        forext,targeting = (True,False)
        
      else:
        print(f"{Color.red}{prefix}! Wrong Input!{Color.default}")
        forext = False
        
    while targeting == True:
      print(f"{prefix}i Separate with space")
      extension,targeting = (input(f"{Color.blue}{prefix}? Which Extension? (example: .com .go.us): {Color.default}").split(" "), False)
    
    print(f"{prefix}i Getting Result..")
    _exec = grab.Service1()
    
    try:
      
      while start_date <= end_date:
        datedump = start_date.strftime("%Y-%m-%d")
        datedump_str = grab.list_to_date(start_date.strftime("%d %m %Y").split(" "))
        
        try:
          
          totalpage = _exec.count_pages(datedump)
          print(f"{prefix} There are {str(totalpage)} pages in {datedump_str}")
          sleep(0.5)
          print(f"{prefix} Dumping all of them..")
          sleep(0.5)
          
          for page in tqdm(range(1,(totalpage+1)),f"{prefix} Grabbbing"):
            result = _exec.dump(datedump,str(page),extension)
            
            for res in result:
              open("grablist.txt","a").write(res+"\n")
              totalresult += [res]
              
          if 1 < len(totalresult) < 1000:
            print(f"{Color.green}{prefix} Ah.. Only {len(totalresult)} of datas you get it. But good.{Color.default}")
            
          elif len(totalresult) == 0:
            print(f"{Color.red}{prefix} Oh no.. There is no data you take it for {datedump_str}{Color.default}")
            
          else:
            print(f"{Color.green}{prefix} Great! getting total {len(totalresult)} of datas in {datedump_str}{Color.default}",end="")
            
        except Exception:
          print(f"{Color.red}{prefix}! Error while processing..{Color.default}")
          
          if totalresult:
            print(f"{Color.green}But you got {len(totalresult)} of datas in {datedump_str}.{Color.default}")
            
          else:
            print(f"{Color.red}Request's timeout in {datedump_str}. Pass it!{Color.default}")
          
        start_date += delta
    
    except KeyboardInterrupt:
      start_date,end_date = (1,0)
      print(f"\n{prefix} Cancelling..")
      exit()
      
    print(f"{prefix}@ Done it, result in grablist.txt")
    
  elif server == "2":
    print(f"{Color.default}{prefix}@ Start using server 2")
    print(f"{Color.yellow}{prefix}i ",end="")
    
    for char in grab.Service2().about:
      print(char,end="",flush=True)
      sleep(0.005)
      
    print(Color.default)
    choosen = False
    
    while choosen == False:
      extension = input(f"{Color.blue}{prefix}? Choose an extension: {Color.default}")
      
      if findall(r"( |\,|\-|\/)",extension):
        print(f"{Color.red}{prefix}! Only an extension you can choose!{Color.default}")
        choosen = False
        
      elif len(findall(r"(\.)",extension)) > 1:
        print(f"{Color.red}{prefix}! You can't choose for sub extension!{Color.default}")
        choosen = False
        
      else: choosen,extension = (True,extension.replace('.',""))
      
      print(f"{prefix}i Checking connection to server.. ",end="",flush=True)
      grab = grab.Service2(extension)
      connection = grab.connect()
    
      if connection[0] == False:
        print(f"{Color.red}Can't connect! choose another extension.{Color.default}")
        choosen = False
        from tool import grab
      
      else: choosen = True
      
    print(f"{Color.green}Great! Let's take the datas{Color.default}")
    pagef = grab.count_pages()
    
    for homepage in tqdm(range(1,(pagef+1)),f"{prefix}Load page"):
      pagel = grab.count_pages(str(homepage))
      print(f"{prefix}Getting total of {str(pagel)} pages including datas in page {str(homepage)}.")
    
      for datapage in tqdm(range(0,pagel),f"{prefix} Grabbing"):
        datares = grab.dump_site(str(homepage),str(datapage))
        
        for domain in datares:
          open("grablist.txt","a").write(domain+"\n")
          
    print(f"{prefix} Result saved in grablist.txt")
    
  else:
    
    while server == "":
      print(f"\n{grab.__info__}")
      a_server = input(f"{Color.blue}{prefix}? Then your choice is: {Color.default}")
      server = findall(r"(1|2)",a_server)
      
      if server:
        
        if server[0] == "1":
          grabber("1")
          
        else:
          grabber("2")
          
      else:
        print(f"{Color.red}{prefix}x incorrect input!{Color.default}")
        server = ""
  
