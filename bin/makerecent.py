#!/usr/bin/env python3
import sys,os,os.path,re
from glob import glob
from collections import defaultdict
from pathlib import Path
import json
from datetime import datetime
args = sys.argv[1:]

if "-v" in args:
  verbose = True
else:
  verbose = False
def p(*xs,**kw):
  if verbose:
    print(*xs,**kw)
verbose = False # TODO remove

pages = []
files = []
files_location = "../files"
def relpath(path):
  return path[len(files_location)+1:]
for path in Path(files_location).rglob("*.*"):
  path = str(path)
  rpath = relpath(path)
  p(f"{path}:({rpath})")
  path_components = path.split("/")
  fn = path_components[-1]
  mtime = os.path.getmtime(path)
  if path.endswith(".ptmd"):
    # page
    p(f"Page: {path}")
    pages.append([mtime,rpath])
  elif os.path.isfile(path):
    p(f"File: {path}")
    files.append([mtime,rpath])
def first(xs):
  return xs[0]
def second(xs):
  return xs[1]
def neg(f):
  return lambda t: -f(t)
recent_pages = list(sorted(pages,key=neg(first)))
recent_files = list(sorted(files,key=neg(first)))
with open("../data/recent_pages.json","wt") as f:
  json.dump(recent_pages,f)
with open("../data/recent_files.json","wt") as f:
  json.dump(recent_files,f)
for mtime, rpath in recent_pages:
  print(datetime.fromtimestamp(mtime).strftime("%Y-%m-%d %H-%M-%S"),rpath)
  
