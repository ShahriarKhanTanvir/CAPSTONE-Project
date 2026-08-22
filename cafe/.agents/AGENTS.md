# Deployment and Server Rules

- **Target Production URL**: [https://mehedihasan.au/kent/cpro306/g1/](https://mehedihasan.au/kent/cpro306/g1/)
- **Live Production Server**:
  - Host: `mehedihasan.au` (`116.255.43.78`)
  - Port: `2222`
  - Username: `mehedih3_cpro306_g1`
  - Password: `cpro306`
  - Remote Directory: `.`
- **Rule**: Whenever deploying or asked to push to the server, ALWAYS deploy all files to this server via `python3 -u deploy.py --auto` from `Ravenhill_Project` so `https://mehedihasan.au/kent/cpro306/g1/` stays updated.
