# Todo Cloud REST API

## *NOTE: THIS README IS A WORK IN PROGRESS AND SHOULD NOT BE CONSIDERED "READY"*

## Deploying to Local Test Machine
TODO: Explain how we use Vagrant

## Deploying to Development Machine (plano.todo-cloud.com)
TODO: Explain the deployment process to [plano.todo-cloud.com](https://plano.todo-cloud.com/).

## Deploying to Amazon (Production System)

#### Pre-requisites
1. Install aws-cli (AWS Command-line interface – search Google for latest version)
2. Configure aws-cli with the Appigo keys so you have a *appigo* profile in ~/.aws/config

```
[profile appigo]
region = us-east-1
aws_access_key_id = 1234567890 (substitute the real access key here)
aws_secret_access_key = +C1sfjdsklf324jfs87432h (substitute the real secret access key here)
```

#### Upload new API functions to AWS API Gateway
`aws xyz --profile appigo`

#### Upload new Lambda functions
`aws xyz --profile appigo`

#### Deploy into Production
TODO: Explain what needs to be done

**See which APIs have been deployed (get the ID of the API)**

```
aws apigateway get-rest-apis --profile appigo
```

**Get the list of resources deployed in the API**

```
get-resources --rest-api-id "55jmtuf9lk" --profile appigo
```

**Update the REST API**

``` 
aws apigateway put-rest-api --rest-api-id "55jmtuf9lk" --mode merge --fail-on-warnings --body "file://todo-cloud-api-v1.json" --profile appigo
```

**Create a New Lambda Function**

```
aws lambda create-function --function-name "createAccount" --runtime "nodejs4.3" --role "arn:aws:iam::398938165940:role/service-role/todo-api-lambda-function-role" --handler "authenticate.createAccount" --description "Create a new Todo Cloud user account." --vpc-config '{"SubnetIds": ["subnet-099f8822","subnet-62f23014"], "SecurityGroupIds": ["sg-901424f6"]}' --environment '{"Variables": {"DB_PASSWORD": "aws\u0021appengine", "DB_NAME": "tdo_db", "DB_HOST": "todo-cloud-aurora-db-cluster.cluster-cwi9sxs6sdl7.us-east-1.rds.amazonaws.com", "DB_USERNAME": "tdoadmin", "SESSION_TIMEOUT": "86400"}}' --zip-file 'fileb://lambda.zip' --profile appigo
```

**Give API Gateway permission to a Lambda Function**

```
aws lambda add-permission --function-name "createAccount" --statement-id api-gateway-access --action "lambda:InvokeFunction" --principal "apigateway.amazonaws.com" --profile appigo
```

**Update Lambda Function CODE**

```
aws lambda update-function-code --function-name "createAccount" --zip-file 'fileb://lambda.zip' --profile appigo
```
