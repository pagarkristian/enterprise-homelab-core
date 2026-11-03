# The Audit Passed. Then the Requests Started Coming In.

TechSecure Indonesia's infrastructure cleared its security audit in v1.1.0. 
Firewall rules locked down, SSH hardened, SQL injection patched. On paper, 
the server was done.

Two weeks later, two requests landed on the admin's desk — neither from 
security, both from people just trying to get their jobs done faster.

## Request 1: HR wants employees uploading their own profile photos

Right now, HR manually uploads every employee photo one by one. With 40+ 
staff and growing, that's a bottleneck nobody signed up for. The ask is 
simple: let employees upload their own photo through the portal, and have 
the system auto-resize it so it doesn't blow up page load times.

Reasonable. Also: the first new file-handling logic since the upload 
vulnerability was patched in v1.1.0 — except this time, the file isn't 
just being *stored*. It's being *processed*.

## Request 2: Supervisors want direct links to payroll slips

A supervisor currently has to log in, navigate to the right employee, and 
find the right month just to forward a payslip. They want something 
simpler — a direct link, like `slip.php?id=5`, they can send straight to 
their manager.

Also reasonable. Also: the kind of request that, if built without a second 
thought, turns "my payslip" into "anyone's payslip" with one changed digit 
in the URL.

## The real question

Both features are low-risk from a business standpoint and high-value for 
the people asking. Nobody's going to say no to either one. 

The question this project actually tries to answer isn't *"should we build 
these?"* — it's: **once we do, will anyone notice if something goes wrong?**

v1.1.0 proved the server could survive known attacks. v2.0.0 is about 
building the features the business actually asked for, deliberately 
wiring in the mistakes a real developer might make under deadline 
pressure — and then finding out whether a SIEM sitting on top of it 
would catch them.
