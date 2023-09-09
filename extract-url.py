from urllib.parse import unquote, urlparse, parse_qs
import re

file_path = "sample.html"
url_pattern = r'http[^&]+'

with open(file_path, "r", encoding="utf-8") as file:
    html = file.read()

pattern = r'href="(.*?)"'

href_values = re.findall(pattern, html)
unique_links = []
unique_values = set()

for link in href_values:
  if link not in unique_values:
    unique_links.append(link)
    unique_values.add(link)

f = open('link_file.txt', 'w')
for link in unique_links:
  if re.search(r'facebook', link):
    parsed_url = urlparse(link)
    query_params = parse_qs(parsed_url.query)
    url = query_params.get('u', [''])[0]
  else:
    url = link
  f.write(f"{url}\n")
f.close()
