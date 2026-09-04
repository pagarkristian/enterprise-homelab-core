#!/usr/bin/env python3
"""
Wazuh Integration: Telegram Alerting

Place this file in /var/ossec/integrations/ on the Wazuh Manager and
mark it executable (chmod 750, owned by root:wazuh).

Register it in /var/ossec/etc/ossec.conf:

    <integration>
      <name>custom-telegram.py</name>
      <level>10</level>
      <alert_format>json</alert_format>
    </integration>

Credentials are read from environment variables so no secret is ever
committed to source control.

Setup:
  1. Create a bot via @BotFather on Telegram, note the token it gives you.
  2. Send /start to your bot, then fetch your chat ID:
       curl -s "https://api.telegram.org/bot<TOKEN>/getUpdates"
     Look for "chat":{"id": <YOUR_CHAT_ID>, ...} in the response.
  3. Set the two environment variables before wazuh-manager starts, e.g.
     by adding them to /etc/systemd/system/wazuh-manager.service.d/override.conf:
       [Service]
       Environment="TELEGRAM_BOT_TOKEN=your-token-here"
       Environment="TELEGRAM_CHAT_ID=your-chat-id-here"
     then: systemctl daemon-reload && systemctl restart wazuh-manager
"""
import sys
import os
import json
import requests

alert_file = sys.argv[1]

TELEGRAM_TOKEN = os.environ.get("TELEGRAM_BOT_TOKEN", "")
TELEGRAM_CHAT_ID = os.environ.get("TELEGRAM_CHAT_ID", "")


def send_telegram_message(text):
    if not TELEGRAM_TOKEN or not TELEGRAM_CHAT_ID:
        with open("/var/ossec/logs/integrations.log", "a") as f:
            f.write("Telegram integration: missing TELEGRAM_BOT_TOKEN or TELEGRAM_CHAT_ID env vars\n")
        return
    url = f"https://api.telegram.org/bot{TELEGRAM_TOKEN}/sendMessage"
    payload = {
        "chat_id": TELEGRAM_CHAT_ID,
        "text": text,
        "parse_mode": "Markdown"
    }
    try:
        requests.post(url, json=payload, timeout=10)
    except Exception as e:
        with open("/var/ossec/logs/integrations.log", "a") as f:
            f.write(f"Telegram send error: {e}\n")


def main():
    with open(alert_file) as f:
        alert_json = json.load(f)

    rule = alert_json.get("rule", {})
    agent = alert_json.get("agent", {})
    data = alert_json.get("data", {})

    description = rule.get("description", "Unknown alert")
    level = rule.get("level", "?")
    rule_id = rule.get("id", "?")
    agent_name = agent.get("name", "unknown")
    srcip = data.get("srcip", "N/A")
    timestamp = alert_json.get("timestamp", "N/A")

    message = (
        f"SOC Alert — Level {level}\n\n"
        f"Description: {description}\n"
        f"Rule ID: {rule_id}\n"
        f"Agent: {agent_name}\n"
        f"Source IP: {srcip}\n"
        f"Time: {timestamp}"
    )

    send_telegram_message(message)


if __name__ == "__main__":
    main()
