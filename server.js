import express from "express";
import { createServer } from "http";
import { Server } from "socket.io";

const app = express();
const server = createServer(app);

const baseUrl = "/api";

const io = new Server(server, {
    cors: {
        origin: "*",
    },
});

const users = {};

// const groups = {};

io.on("connection", (socket) => {
    console.log(`User connected: ${socket.id}`);

    socket.on('login', ({userId}) =>{
        console.log(`User ${userId} logged in`);
        updateUserStatus(userId, true);
    })

    //Handle private messages
    socket.on("joinRoom", ({ userId, receiverId }) => {
        users[userId] = socket.id;
        const roomId = [userId, receiverId].sort().join("-");
        socket.join(roomId);
        console.log(`User ${userId} joined room ${roomId}`);
        console.log("roomId:", roomId);
        //update user status
        // updateUserStatus(userId, true);

        socket.on(
            "send_message",
            ({
                userId,
                receiverId,
                conversation_id,
                message,
            }) => {
                const roomId = [userId, receiverId].sort().join("-");
                io.to(roomId).emit("receive_message", {
                    conversation_id,
                });
                console.log(`Message in room ${roomId}: ${message}`);
            }
        );

        //Handle group messages
        socket.on("joinGroup", ({ userId, groupId }) => {
            socket.join(groupId);
            console.log(`User ${userId} joined group ${groupId}`);
        });
        socket.on("sendGroupMessage", ({ groupId, userId, message }) => {
            io.to(groupId).emit("receiveGroupMessage", {
                senderId: userId,
                message,
                timeStamp: new Date().toLocaleString("en-GB", {
                    day: "2-digit",
                    month: "short",
                    hour: "2-digit",
                    minute: "2-digit",
                    hour12: true,
                }),
            });
            console.log(
                `Message in group ${groupId} from ${userId}: ${message}`
            );
        });

        //handle user disconnection
        socket.on("disconnect", () => {
            console.log(`User disconnected: ${socket.id}`);
            const userId = getKeyByValue(users, socket.id);
            delete users[userId];
            console.log(`User ${userId} disconnected`);

            updateUserStatus(userId, false);
        });

        // Get key from value in object
        function getKeyByValue(object, value) {
            return Object.keys(object).find((key) => object[key] === value);
        }
    });
});
server.listen(3000, "10.0.80.13", () => {
    console.log("Server running on http://10.0.80.13:3000");
});

//user status update function
function updateUserStatus(userId, isActive) {
    try {
        fetch(`${baseUrl}/update-status`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                userId,
                is_active: isActive,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                console.log("Success:", data);
            })
            .catch((error) => {
                console.error("Error:", error);
            });
    } catch (error) {
        console.log("Error:", error);
    }
}
