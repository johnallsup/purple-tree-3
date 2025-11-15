#!/usr/bin/env python3
import sys,os,os.path,re
from glob import glob
from collections import defaultdict
from pathlib import Path
from datetime import datetime
import argparse
import json
import re

class Log:
  def __init__(self):
    now = datetime.now().strftime("%Y-%m-%d_%H:%M:%S")
    self.now = now
    self.f = None
  def __call__(self,*xs,**kw):
    if self.f is None:
      self.f = open(f"../log/reindex_{self.now}.log","wt")
    if "file" in kw:
      del(kw["file"])
    print(f"[{self.now}]:",*xs,file=self.f,**kw)
log = Log()

def dump_json(obj,filename):
  with open(filename,"wt") as f:
    json.dump(obj,f)

class Indexer:
  def __init__(self,site_dir,verbose=False):
    self.site_dir = site_dir
    self.data_dir = f"{self.site_dir}/data"
    self.files_dir = f"{self.site_dir}/files"
    self.verbose = verbose
  def printv(self,*xs):
    if self.verbose:
      print(*xs)
  def dp(self,fn):
    return self.data_dir+"/"+fn
  def path_relative_to_files_root(self,path):
    #print("yyyy",path,self.files_dir)
    files_dir = self.files_dir
    if files_dir.startswith("./"):
      files_dir = files_dir[2:]
    return path[len(files_dir)+1:]
  def handleLink(self,m,links):
    a = m.group()
    self.printv(f"Link: {a}")
    if a[0] == "[":
      try:
        if a[1] == "[":
          s = a[2:-2]
        else:
          s = a.split("(")[1][:-1]
      except IndexError:
        self.printv(f"#Fail {a}")
        exit()
    else:
      if not re.search(r"[a-z]",a):
        return
      s = a
    if ":" in s:
      return
    links.add(s)

  def go(self):
    self.links_out = defaultdict(set)
    self.links_in = defaultdict(set)
    self.by_tag = defaultdict(set)
    self.by_word_case_sensitive = defaultdict(set)
    self.by_word_ignore_case = defaultdict(set)
    self.by_page_name = defaultdict(set)
    self.all_words = set()
    self.all_tags = set()
    self.tags_by_dir = defaultdict(set)
    self.pages = []
    self.files = []
    self.pages_mtimes = []
    self.files_mtimes = []
    self.dirs = set()
    self.files_dir = self.files_dir
    self.data_dir = self.data_dir

    for path in Path(self.files_dir).rglob("*.*"):
      path = str(path)
      rpath = self.path_relative_to_files_root(path)
      self.printv(f"{path}:({rpath})")
      path_components = rpath.split("/")
      fn = path_components[-1]
      if len(path_components) > 1: 
        dirname = "/".join(path_components[:-1])
        self.dirs.add(dirname)
      if path.endswith(".ptmd"):
        # page
        self.printv("  Page: {path}")
        self.procpage(path)
      else:
        self.printv(f"  File: {path}")
        self.procfile(path)

    by_tag_l = { k:list(v) for k,v in self.by_tag.items() }
    by_word_cs = { word:list(sorted(pages)) for word,pages in self.by_word_case_sensitive.items() }
    by_word_ic = { word:list(sorted(pages)) for word,pages in self.by_word_ignore_case.items() }
    links_out_a = { pagename:list(sorted(links)) for pagename,links in self.links_out.items() }
    links_in_a = { pagename:list(sorted(links)) for pagename,links in self.links_in.items() }
    tag_lists = { k.lstrip("/"):list(sorted(v)) for k,v in self.tags_by_dir.items() }
    dump_json(tag_lists,self.dp("tag_lists.json"))
    dump_json(list(sorted(self.all_words)),self.dp("all_words.json"))
    dump_json(list(sorted(self.all_tags)),self.dp("all_tags.json"))
    dump_json(by_tag_l,self.dp("by_tag.json"))
    dump_json(by_word_cs,self.dp("by_word_cs.json"))
    dump_json(by_word_ic,self.dp("by_word_ic.json"))
    dump_json(self.pages,self.dp("pages.json"))
    dump_json(self.files,self.dp("files.json"))
    dirs_list = [x for x in self.dirs if x != "" ]
    dump_json(list(sorted(dirs_list)),self.dp("dirs.json"))
    dump_json(links_out_a,self.dp("links_out.json"))
    dump_json(links_in_a,self.dp("links_in.json"))
    #self.printv(f"Tags: {', '.join(sorted(self.all_tags))}")
    #self.printv(f"Words: {', '.join(sorted(self.all_words))}")

    # recent
    recent_pages = list(sorted(self.pages_mtimes,key=lambda t: -t[0]))
    recent_files = list(sorted(self.files_mtimes,key=lambda t: -t[0]))
    dump_json(recent_pages,self.dp("recent_pages.json"))
    dump_json(recent_files,self.dp("recent_files.json"))
    
    self.clear_up_very_recent()

  def clear_up_very_recent(self):
    vrfn = self.dp("recent_writes.log")
    if os.path.exists(vrfn):
      self.printv(f"Removing {vrfn}")
      os.unlink(vrfn)

  def procpage(self,path):
    self.printv(f"  ProcPage: {path}")
    mtime = os.path.getmtime(path)
    rpath = self.path_relative_to_files_root(path)
    #print("xxx",path,rpath)
    self.pages_mtimes.append((mtime,rpath))
    path_components = rpath.split("/")
    pagename = path_components[-1]
    if len(path_components) > 1: 
      dirname = "/".join(path_components[:-1])
      self.dirs.add(self.path_relative_to_files_root(dirname))
    else:
      dirname = ""
    try:
      src = open(path).read().strip()
    except Exception as e:
      log(f"Failed to open {path} -- {e}")
      return
    src = src.replace("\r","")
    lines = src.splitlines()
    if len(lines) == 0:
      return
    meta = {}
    while (len(lines) > 0) and (m := re.match(r"([a-z]+):\s+(.*)",lines[0])):
      k,v = m.groups()
      meta[k] = v
      lines = lines[1:]
    if "tags" in meta:
      tags = meta["tags"]
      tags = re.split(r"[^a-zA-Z0-9_-]+",tags.strip())
      for tag in tags:
        if len(tag) == 0:
          continue
        t = [""]
        for pc in path_components[:-1]:
          t.append(t[-1]+"/"+pc)
        for x in t:
          self.tags_by_dir[x].add(tag)
        tag = tag.lower()
        tag = tag.lstrip("#")
        self.all_tags.add(tag)
        self.by_tag[tag].add(rpath)
      lines = lines[1:]
    for line in lines:
      line = re.sub(r"[^a-zA-Z0-9_-]+"," ",line).strip()
      words = line.split(" ")
      for word in words:
        if len(word) == 0:
          continue
        self.all_words.add(word)
        self.by_word_case_sensitive[word].add(rpath)
        #self.printv(path,word,self.by_word_case_sensitive[word])
        word = word.lower()
        self.by_word_ignore_case[word].add(rpath)
    # WTF
    #self.by_word_case_sensitive[""].add(rpath)
    #self.by_word_ignore_case[""].add(rpath)
    # process links
    src = "\n".join(lines)
    src = re.sub(r"^(`{3,}).*?^\1"," BLOCK ",src)
    src = re.sub(r"(`+).*?\1"," CODE ",src)
    links = set()
    for m in re.finditer(
        r"\[\[.*?\]\]|\[[^\]]*\]\([^)]+\)|[A-Z][A-Za-z0-9_]*[A-Z][A-Za-z0-9_]*",
        src):
      #self.printv(f"Match m={m} m.group()={m.group()}")
      self.handleLink(m,links)
    for link in links:
      if not link.startswith("/"):
        if len(dirname) > 0:
          link = dirname+"/"+link
      self.links_out[rpath].add(link)
      self.links_in[link].add(rpath)
    self.pages.append(rpath)
    self.by_page_name[pagename]

  def procfile(self,path):
    rpath = self.path_relative_to_files_root(path)
    mtime = os.path.getmtime(path)
    self.pages_mtimes.append((mtime,rpath))
    self.files.append(rpath)

def main():
  parser = argparse.ArgumentParser(prog="makeindex_data",
                                  description="Make index for a PT3 site")
  parser.add_argument("-v","--verbose",action="store_true")
  parser.add_argument("-q","--quiet",action="store_true")
  parser.add_argument("sites",help="Root of site (parent of docs root dir)",nargs="+")
  ns = parser.parse_args()

  for site_dir in ns.sites:
    if not os.path.isdir(site_dir):
      print(f"{site_dir} is not a directory, skipping")
      continue
    indexer = Indexer(site_dir=site_dir,verbose=ns.verbose)
    indexer.go()

if __name__ == "__main__":
  main()

